<?php

namespace App\Modules\Conversations\Actions;

use App\Modules\Conversations\Enums\ConversationStatus;
use App\Modules\Conversations\Enums\MessageAuthorType;
use App\Modules\Conversations\Events\HumanHandoverRequested;
use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Models\Message;

class RequestHumanHandover
{
    public static function run(Conversation $conversation): Message
    {
        $conversation->forceFill([
            'status' => ConversationStatus::NeedsHuman,
            'ai_enabled' => false,
            'human_handover_at' => $conversation->human_handover_at ?? now(),
        ])->save();
        HumanHandoverRequested::dispatch($conversation);

        return AddMessage::run(
            $conversation,
            (string) config('maracuja.conversations.public.handover_message'),
            MessageAuthorType::System,
        );
    }
}
