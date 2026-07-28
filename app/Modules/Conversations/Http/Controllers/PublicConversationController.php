<?php

namespace App\Modules\Conversations\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Conversations\Actions\AddMessage;
use App\Modules\Conversations\Actions\BeginCallbackCollection;
use App\Modules\Conversations\Actions\CollectCallbackDetail;
use App\Modules\Conversations\Actions\FindAnonymousConversation;
use App\Modules\Conversations\Actions\GenerateAiReply;
use App\Modules\Conversations\Actions\RequestHumanHandover;
use App\Modules\Conversations\Actions\StartAnonymousConversation;
use App\Modules\Conversations\Enums\ConversationStatus;
use App\Modules\Conversations\Enums\MessageAuthorType;
use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Models\ConversationSetting;
use App\Modules\Conversations\Support\WhatsAppHandoverLink;
use App\Support\Modules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicConversationController extends Controller
{
    private const SESSION_ID = 'maracuja.conversation.id';

    private const SESSION_TOKEN = 'maracuja.conversation.token';

    public function show(Request $request): JsonResponse
    {
        abort_unless($this->enabled(), 404);

        $conversation = $this->fromSession($request);

        return response()->json($conversation === null
            ? [
                'conversation' => null,
                'messages' => [],
                'whatsapp_url' => null,
            ]
            : $this->payload($conversation));
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($this->enabled(), 404);

        $data = $request->validate([
            'website' => ['nullable', 'string', 'max:0'],
            'content' => ['required', 'string', 'min:1', 'max:5000'],
            'entry_url' => ['nullable', 'url', 'max:2048'],
        ]);

        $conversation = $this->fromSession($request);

        if ($conversation === null) {
            $anonymousSession = StartAnonymousConversation::run(
                locale: $request->getPreferredLanguage(),
                entryUrl: $data['entry_url'] ?? null,
            );
            $conversation = $anonymousSession->conversation;
            $request->session()->put(self::SESSION_ID, $conversation->getKey());
            $request->session()->put(self::SESSION_TOKEN, $anonymousSession->token);
        }

        abort_if(
            in_array($conversation->status, [ConversationStatus::Closed, ConversationStatus::Archived], true),
            409,
            'Cette conversation est terminée.',
        );
        abort_if(
            $conversation->inquiry()->exists(),
            409,
            'Cette demande est enregistrée et ne reçoit plus de nouveaux messages.',
        );

        $collectingCallback = $this->collectingCallback($conversation);
        AddMessage::run($conversation, $data['content'], MessageAuthorType::Visitor);

        if ($collectingCallback) {
            CollectCallbackDetail::run($conversation, $data['content']);
        } elseif ($conversation->ai_enabled && $conversation->status !== ConversationStatus::HumanActive) {
            GenerateAiReply::run($conversation);
        }

        return response()->json($this->payload($conversation->refresh()), 201);
    }

    public function handover(Request $request): JsonResponse
    {
        abort_unless($this->enabled(), 404);

        $conversation = $this->fromSession($request);
        abort_if($conversation === null, 404);
        abort_if(
            in_array($conversation->status, [ConversationStatus::Closed, ConversationStatus::Archived], true),
            409,
        );

        if ($conversation->status !== ConversationStatus::NeedsHuman) {
            RequestHumanHandover::run($conversation);
        }

        return response()->json($this->payload($conversation->refresh()));
    }

    public function callback(Request $request): JsonResponse
    {
        abort_unless(
            $this->enabled()
                && Modules::enabled('inquiries')
                && ConversationSetting::current()->callback_enabled,
            404,
        );

        $conversation = $this->fromSession($request);
        abort_if($conversation === null, 404);

        if (! $this->collectingCallback($conversation) && $conversation->inquiry()->doesntExist()) {
            BeginCallbackCollection::run($conversation);
        }

        return response()->json($this->payload($conversation->refresh()));
    }

    private function fromSession(Request $request): ?Conversation
    {
        $id = $request->session()->get(self::SESSION_ID);
        $token = $request->session()->get(self::SESSION_TOKEN);

        if (! is_numeric($id) || ! is_string($token)) {
            return null;
        }

        return FindAnonymousConversation::run((int) $id, $token);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Conversation $conversation): array
    {
        $settings = ConversationSetting::current();
        $offerContactOptions = (bool) data_get(
            $conversation->qualification,
            '_routing.contact_options_suggested',
            false,
        );

        return [
            'conversation' => [
                'reference' => $conversation->public_reference,
                'status' => $conversation->status->value,
                'awaiting_human' => in_array($conversation->status, [
                    ConversationStatus::NeedsHuman,
                    ConversationStatus::HumanActive,
                ], true),
                'collecting_contact' => $this->collectingCallback($conversation),
                'inquiry_created' => $conversation->inquiry()->exists(),
                'offer_contact_options' => $offerContactOptions,
                'callback_enabled' => $offerContactOptions && $settings->callback_enabled,
                'whatsapp_url' => $offerContactOptions && $settings->whatsapp_enabled
                    ? WhatsAppHandoverLink::make($conversation)
                    : null,
            ],
            'messages' => $conversation->publicMessages()
                ->oldest('sent_at')
                ->get()
                ->map(fn ($message): array => [
                    'id' => $message->getKey(),
                    'author' => $message->author_type->value,
                    'content' => $message->content,
                    'sent_at' => $message->sent_at?->toIso8601String(),
                ])
                ->all(),
            'whatsapp_url' => null,
        ];
    }

    private function collectingCallback(Conversation $conversation): bool
    {
        return filled(data_get($conversation->qualification, 'callback.step'));
    }

    private function enabled(): bool
    {
        return Modules::enabled('conversations') && ConversationSetting::current()->is_enabled;
    }
}
