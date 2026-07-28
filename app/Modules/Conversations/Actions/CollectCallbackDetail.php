<?php

namespace App\Modules\Conversations\Actions;

use App\Modules\Conversations\Enums\ConversationStatus;
use App\Modules\Conversations\Enums\MessageAuthorType;
use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Models\Message;
use Illuminate\Support\Facades\Validator;

class CollectCallbackDetail
{
    public static function run(Conversation $conversation, string $answer): Message
    {
        $qualification = $conversation->qualification ?? [];
        $callback = $qualification['callback'] ?? null;
        $step = is_array($callback) ? ($callback['step'] ?? null) : null;
        $data = is_array($callback['data'] ?? null) ? $callback['data'] : [];

        return match ($step) {
            'name' => self::name($conversation, $qualification, $data, $answer),
            'preference' => self::preference($conversation, $qualification, $data, $answer),
            'coordinate' => self::coordinate($conversation, $qualification, $data, $answer),
            'consent' => self::consent($conversation, $qualification, $data, $answer),
            default => BeginCallbackCollection::run($conversation),
        };
    }

    private static function name(Conversation $conversation, array $qualification, array $data, string $answer): Message
    {
        $name = trim($answer);
        if (mb_strlen($name) < 2 || mb_strlen($name) > 120) {
            return self::prompt($conversation, config('maracuja.conversations.callback.invalid_name'));
        }

        $data['name'] = $name;
        self::saveStep($conversation, $qualification, 'preference', $data);

        return self::prompt($conversation, config('maracuja.conversations.callback.ask_preference'));
    }

    private static function preference(Conversation $conversation, array $qualification, array $data, string $answer): Message
    {
        $answer = mb_strtolower(trim($answer));
        $preference = str_contains($answer, 'mail')
            ? 'email'
            : (str_contains($answer, 'whats') ? 'whatsapp' : (str_contains($answer, 'tél') || str_contains($answer, 'tel') ? 'phone' : null));

        if ($preference === null) {
            return self::prompt($conversation, config('maracuja.conversations.callback.invalid_preference'));
        }

        $data['preferred_contact'] = $preference;
        self::saveStep($conversation, $qualification, 'coordinate', $data);

        return self::prompt(
            $conversation,
            $preference === 'email'
                ? config('maracuja.conversations.callback.ask_email')
                : config('maracuja.conversations.callback.ask_phone'),
        );
    }

    private static function coordinate(Conversation $conversation, array $qualification, array $data, string $answer): Message
    {
        $preference = $data['preferred_contact'];
        $key = $preference === 'email' ? 'email' : 'phone';
        $rules = $key === 'email'
            ? ['value' => ['required', 'email:rfc', 'max:160']]
            : ['value' => ['required', 'string', 'max:60', 'regex:/^(?=(?:\\D*\\d){8,})[+\\d\\s().-]+$/']];

        if (Validator::make(['value' => trim($answer)], $rules)->fails()) {
            return self::prompt(
                $conversation,
                $key === 'email'
                    ? config('maracuja.conversations.callback.invalid_email')
                    : config('maracuja.conversations.callback.invalid_phone'),
            );
        }

        $data[$key] = trim($answer);
        self::saveStep($conversation, $qualification, 'consent', $data);

        return self::prompt($conversation, config('maracuja.conversations.callback.ask_consent'));
    }

    private static function consent(Conversation $conversation, array $qualification, array $data, string $answer): Message
    {
        $answer = mb_strtolower(trim($answer));
        $accepted = in_array($answer, ['oui', 'yes', 'sim', 'd’accord', 'daccord', 'ok'], true);
        $refused = in_array($answer, ['non', 'no', 'não', 'nao'], true);

        if (! $accepted && ! $refused) {
            return self::prompt($conversation, config('maracuja.conversations.callback.invalid_consent'));
        }

        unset($qualification['callback']);
        $conversation->update([
            'qualification' => $qualification,
            'status' => ConversationStatus::NeedsHuman,
            'ai_enabled' => false,
        ]);

        if (! $accepted) {
            return self::prompt($conversation, config('maracuja.conversations.callback.consent_refused'), MessageAuthorType::System);
        }

        CreateInquiryFromConversation::run($conversation, [
            ...$data,
            'consent' => true,
        ]);

        return self::prompt($conversation, config('maracuja.conversations.callback.completed'), MessageAuthorType::System);
    }

    private static function saveStep(Conversation $conversation, array $qualification, string $step, array $data): void
    {
        $qualification['callback'] = compact('step', 'data');
        $conversation->update(['qualification' => $qualification]);
    }

    private static function prompt(
        Conversation $conversation,
        string $content,
        MessageAuthorType $author = MessageAuthorType::Ai,
    ): Message {
        return AddMessage::run($conversation, $content, $author);
    }
}
