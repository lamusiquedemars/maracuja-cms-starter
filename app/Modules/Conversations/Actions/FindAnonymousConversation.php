<?php

namespace App\Modules\Conversations\Actions;

use App\Modules\Conversations\Models\Conversation;

class FindAnonymousConversation
{
    public static function run(int $conversationId, string $token): ?Conversation
    {
        if ($token === '') {
            return null;
        }

        $conversation = Conversation::query()->find($conversationId);

        if ($conversation === null || ! $conversation->belongsToAnonymousToken($token)) {
            return null;
        }

        return $conversation;
    }
}
