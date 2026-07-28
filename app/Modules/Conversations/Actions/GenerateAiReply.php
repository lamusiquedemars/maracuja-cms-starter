<?php

namespace App\Modules\Conversations\Actions;

use App\Modules\Conversations\Enums\ConversationStatus;
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

            $conversation->forceFill([
                'status' => ConversationStatus::NeedsHuman,
                'ai_enabled' => false,
                'human_handover_at' => now(),
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
