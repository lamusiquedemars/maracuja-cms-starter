<?php

namespace App\Modules\Audience\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SegmentMessageDelivery extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SENDING = 'sending';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SKIPPED = 'skipped';

    protected $fillable = [
        'segment_message_id',
        'audience_contact_id',
        'email',
        'status',
        'attempts',
        'attempted_at',
        'error_message',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'attempted_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public function segmentMessage(): BelongsTo
    {
        return $this->belongsTo(SegmentMessage::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(AudienceContact::class, 'audience_contact_id');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'À envoyer',
            self::STATUS_SENDING => 'En cours',
            self::STATUS_SENT => 'Remis au serveur mail',
            self::STATUS_FAILED => 'Refus immédiat',
            self::STATUS_SKIPPED => 'Exclu',
            default => 'Statut inconnu',
        };
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            self::STATUS_SENT => 'success',
            self::STATUS_FAILED => 'danger',
            self::STATUS_SENDING => 'warning',
            self::STATUS_SKIPPED => 'gray',
            default => 'info',
        };
    }

    public function domain(): string
    {
        $parts = explode('@', $this->email);

        return strtolower(end($parts) ?: '');
    }
}
