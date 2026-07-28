<?php

namespace App\Modules\Conversations\Services;

use App\Modules\Conversations\Actions\AddMessage;
use App\Modules\Conversations\Contracts\ConversationAiProvider;
use App\Modules\Conversations\Data\AiConversationRequest;
use App\Modules\Conversations\Enums\ConversationStatus;
use App\Modules\Conversations\Enums\MessageAuthorType;
use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Models\ConversationSetting;
use App\Modules\Conversations\Models\Message;

class AiConversationService
{
    public function __construct(
        private readonly ConversationAiProvider $provider,
    ) {}

    public function reply(Conversation $conversation): Message
    {
        $historyLimit = (int) config('maracuja.conversations.ai.history_messages', 12);

        $messages = $conversation->publicMessages()
            ->latest('sent_at')
            ->limit($historyLimit)
            ->get()
            ->reverse()
            ->map(fn (Message $message): array => [
                'role' => $message->author_type === MessageAuthorType::Visitor ? 'user' : 'assistant',
                'content' => $message->content,
            ])
            ->values()
            ->all();

        $result = $this->provider->respond(new AiConversationRequest(
            instructions: app(ConversationInstructionsBuilder::class)->build(ConversationSetting::current()),
            messages: $messages,
            safetyIdentifier: hash_hmac('sha256', (string) $conversation->getKey(), (string) config('app.key')),
        ));

        $conversation->forceFill([
            'summary' => $result->summary,
            'topic' => $result->topic,
            'urgency' => $result->urgency,
            'qualification' => [
                ...$result->qualification,
                '_routing' => [
                    'contact_options_suggested' => $result->offerContactOptions,
                ],
            ],
            'status' => $result->requiresHuman
                ? ConversationStatus::NeedsHuman
                : ConversationStatus::AiActive,
            'ai_enabled' => ! $result->requiresHuman,
            'human_handover_at' => $result->requiresHuman ? now() : null,
        ])->save();

        return AddMessage::run(
            $conversation,
            $result->reply,
            MessageAuthorType::Ai,
        );
    }
}
