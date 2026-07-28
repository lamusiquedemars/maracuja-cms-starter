<?php

namespace App\Modules\Conversations\Actions;

use App\Modules\Conversations\Enums\ConversationStatus;
use App\Modules\Conversations\Enums\HandoverReason;
use App\Modules\Conversations\Enums\MessageAuthorType;
use App\Modules\Conversations\Events\HumanHandoverRequested;
use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Models\Message;

class RequestHumanHandover
{
    public static function run(
        Conversation $conversation,
        HandoverReason $reason = HandoverReason::VisitorRequest,
        ?string $message = null,
    ): Message
    {
        $qualification = $conversation->qualification ?? [];
        data_set($qualification, '_routing.contact_options_suggested', true);

        $conversation->forceFill([
            'status' => ConversationStatus::NeedsHuman,
            'ai_enabled' => false,
            'qualification' => $qualification,
            'human_handover_at' => $conversation->human_handover_at ?? now(),
            'handover_reason' => $reason,
        ])->save();
        HumanHandoverRequested::dispatch($conversation);

        return AddMessage::run(
            $conversation,
            $message ?? (string) config('maracuja.conversations.public.handover_message'),
            MessageAuthorType::System,
        );
    }
}
