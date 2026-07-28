<?php

namespace App\Modules\Conversations\Actions;

use App\Modules\Conversations\Enums\ConversationStatus;
use App\Modules\Conversations\Enums\HandoverReason;
use App\Modules\Conversations\Enums\MessageAuthorType;
use App\Modules\Conversations\Exceptions\AiProviderException;
use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Models\Message;
use App\Modules\Conversations\Services\AiConversationService;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateAiReply
{
    public static function run(Conversation $conversation): Message
    {
        try {
            return app(AiConversationService::class)->reply($conversation);
        } catch (Throwable $exception) {
            Log::warning('Conversation AI provider failed.', [
                'conversation_id' => $conversation->getKey(),
                'provider' => config('maracuja.conversations.ai.provider'),
                'exception' => $exception::class,
            ]);

            $qualification = $conversation->qualification ?? [];
            data_set($qualification, '_routing.contact_options_suggested', true);

            $conversation->forceFill([
                'status' => ConversationStatus::NeedsHuman,
                'ai_enabled' => false,
                'qualification' => $qualification,
                'human_handover_at' => now(),
                'handover_reason' => HandoverReason::TechnicalError,
            ])->save();

            if (! $exception instanceof AiProviderException) {
                report($exception);
            }

            return AddMessage::run(
                $conversation,
                (string) config('maracuja.conversations.ai.fallback_message'),
                MessageAuthorType::System,
            );
        }
    }
}
