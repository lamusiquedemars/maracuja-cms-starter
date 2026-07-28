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
        do {
            $reference = Str::upper(Str::random(10));
        } while (Conversation::query()->where('public_reference', $reference)->exists());

        return $reference;
    }
}
