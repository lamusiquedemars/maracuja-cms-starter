<?php

namespace App\Modules\Conversations\Models;

use App\Models\User;
use App\Modules\Conversations\Enums\ConversationChannel;
use App\Modules\Conversations\Enums\MessageAuthorType;
use App\Modules\Conversations\Enums\MessageDeliveryStatus;
use App\Modules\Conversations\Enums\MessageVisibility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $table = 'conversation_messages';

    protected $fillable = [
        'conversation_id',
        'author_user_id',
        'author_type',
        'content',
        'channel',
        'visibility',
        'delivery_status',
        'external_id',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'author_type' => MessageAuthorType::class,
            'channel' => ConversationChannel::class,
            'visibility' => MessageVisibility::class,
            'delivery_status' => MessageDeliveryStatus::class,
            'sent_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function authorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }
}
