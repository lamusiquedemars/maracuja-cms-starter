<?php

namespace App\Modules\Conversations\Actions;

use App\Models\User;
use App\Modules\Conversations\Enums\MessageAuthorType;
use App\Modules\Conversations\Enums\MessageDeliveryStatus;
use App\Modules\Conversations\Enums\MessageVisibility;
use App\Modules\Conversations\Events\MessageAdded;
use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Models\Message;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AddMessage
{
    public static function run(
        Conversation $conversation,
        string $content,
        MessageAuthorType $authorType,
        MessageVisibility $visibility = MessageVisibility::Public,
        ?User $author = null,
    ): Message {
        $content = trim($content);

        if ($content === '' || mb_strlen($content) > 5000) {
            throw ValidationException::withMessages([
                'content' => 'Le message doit contenir entre 1 et 5 000 caractères.',
            ]);
        }

        if ($visibility === MessageVisibility::Internal && $authorType === MessageAuthorType::Visitor) {
            throw ValidationException::withMessages([
                'visibility' => 'Un visiteur ne peut pas créer une note interne.',
            ]);
        }

        return DB::transaction(function () use ($conversation, $content, $authorType, $visibility, $author): Message {
            $sentAt = now();

            $message = $conversation->messages()->create([
                'author_user_id' => $author?->getKey(),
                'author_type' => $authorType,
                'content' => $content,
                'channel' => $conversation->channel,
                'visibility' => $visibility,
                'delivery_status' => MessageDeliveryStatus::Sent,
                'sent_at' => $sentAt,
            ]);

            $conversation->forceFill(['last_message_at' => $sentAt])->save();
            MessageAdded::dispatch($message);

            return $message;
        });
    }
}
