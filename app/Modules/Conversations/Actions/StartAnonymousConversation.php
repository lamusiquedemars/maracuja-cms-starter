<?php

namespace App\Modules\Conversations\Actions;

use App\Modules\Conversations\Data\AnonymousConversationSession;
use App\Modules\Conversations\Enums\ConversationChannel;
use App\Modules\Conversations\Enums\ConversationStatus;
use App\Modules\Conversations\Enums\ConversationUrgency;
use App\Modules\Conversations\Events\ConversationStarted;
use App\Modules\Conversations\Models\Conversation;
use Illuminate\Support\Str;

class StartAnonymousConversation
{
    private const REFERENCE_ALPHABET = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';

    public static function run(
        ConversationChannel $channel = ConversationChannel::Website,
        ?string $locale = null,
        ?string $entryUrl = null,
    ): AnonymousConversationSession {
        $token = Str::random(64);

        $conversation = Conversation::query()->create([
            'public_reference' => self::uniqueReference(),
            'session_token_hash' => hash('sha256', $token),
            'channel' => $channel,
            'status' => ConversationStatus::New,
            'urgency' => ConversationUrgency::Unknown,
            'locale' => $locale,
            'entry_url' => $entryUrl,
            'ai_enabled' => true,
        ]);

        ConversationStarted::dispatch($conversation);

        return new AnonymousConversationSession($conversation, $token);
    }

    private static function uniqueReference(): string
    {
        $length = min(16, max(6, (int) config('maracuja.conversations.reference_length', 8)));

        do {
            $reference = self::randomReference($length);
        } while (Conversation::query()->where('public_reference', $reference)->exists());

        return $reference;
    }

    private static function randomReference(int $length): string
    {
        $lastIndex = strlen(self::REFERENCE_ALPHABET) - 1;
        $reference = '';

        for ($index = 0; $index < $length; $index++) {
            $reference .= self::REFERENCE_ALPHABET[random_int(0, $lastIndex)];
        }

        return $reference;
    }
}
