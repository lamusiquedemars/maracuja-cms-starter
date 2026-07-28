<?php

namespace App\Modules\Conversations\Actions;

use App\Modules\Conversations\Enums\ConversationStatus;
use App\Modules\Conversations\Enums\MessageAuthorType;
use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Models\Message;

class BeginCallbackCollection
{
    public static function run(Conversation $conversation): Message
    {
        $qualification = $conversation->qualification ?? [];
        $qualification['callback'] = [
            'step' => 'name',
            'data' => [],
        ];

        $conversation->update([
            'status' => ConversationStatus::WaitingForVisitor,
            'ai_enabled' => false,
            'qualification' => $qualification,
            'human_handover_at' => $conversation->human_handover_at ?? now(),
        ]);

        return AddMessage::run(
            $conversation,
            (string) config('maracuja.conversations.callback.ask_name'),
            MessageAuthorType::Ai,
        );
    }
}
