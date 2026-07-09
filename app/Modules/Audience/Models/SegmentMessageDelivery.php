<?php

namespace App\Modules\Audience\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SegmentMessageDelivery extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_TARGETED = 'targeted';
    public const STATUS_EXCLUDED = 'excluded';
    public const STATUS_SYNCED_TO_BREVO = 'synced_to_brevo';
    public const STATUS_SENDING = 'sending';
    public const STATUS_SENT_TO_PROVIDER = 'sent_to_provider';
    public const STATUS_SENT = 'sent';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_OPENED = 'opened';
    public const STATUS_CLICKED = 'clicked';
    public const STATUS_SOFT_BOUNCED = 'soft_bounced';
    public const STATUS_HARD_BOUNCED = 'hard_bounced';
    public const STATUS_UNSUBSCRIBED = 'unsubscribed';
    public const STATUS_COMPLAINED = 'complained';
    public const STATUS_BLOCKED = 'blocked';
    public const STATUS_ERROR = 'error';
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
        'provider_status',
        'latest_event',
        'latest_event_at',
        'delivered_at',
        'opened_at',
        'clicked_at',
        'soft_bounced_at',
        'hard_bounced_at',
        'unsubscribed_at',
        'complained_at',
        'bounce_reason',
        'brevo_raw_event_id',
    ];

    protected function casts(): array
    {
        return [
            'attempted_at' => 'datetime',
            'sent_at' => 'datetime',
            'latest_event_at' => 'datetime',
            'delivered_at' => 'datetime',
            'opened_at' => 'datetime',
            'clicked_at' => 'datetime',
            'soft_bounced_at' => 'datetime',
            'hard_bounced_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
            'complained_at' => 'datetime',
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
            self::STATUS_TARGETED => 'Ciblé',
            self::STATUS_EXCLUDED, self::STATUS_SKIPPED => 'Exclu',
            self::STATUS_SYNCED_TO_BREVO => 'Synchronisé Brevo',
            self::STATUS_SENDING => 'En cours',
            self::STATUS_SENT_TO_PROVIDER => 'Envoyé à Brevo',
            self::STATUS_SENT => 'Remis au serveur mail',
            self::STATUS_DELIVERED => 'Délivré',
            self::STATUS_OPENED => 'Ouvert',
            self::STATUS_CLICKED => 'Cliqué',
            self::STATUS_SOFT_BOUNCED => 'Soft bounce',
            self::STATUS_HARD_BOUNCED => 'Hard bounce',
            self::STATUS_UNSUBSCRIBED => 'Désinscrit',
            self::STATUS_COMPLAINED => 'Plainte spam',
            self::STATUS_BLOCKED => 'Bloqué',
            self::STATUS_ERROR => 'Erreur',
            self::STATUS_FAILED => 'Refus immédiat',
            default => 'Statut inconnu',
        };
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            self::STATUS_SENT => 'success',
            self::STATUS_DELIVERED, self::STATUS_OPENED, self::STATUS_CLICKED => 'success',
            self::STATUS_FAILED, self::STATUS_ERROR, self::STATUS_HARD_BOUNCED, self::STATUS_COMPLAINED => 'danger',
            self::STATUS_SENDING, self::STATUS_SENT_TO_PROVIDER, self::STATUS_SOFT_BOUNCED => 'warning',
            self::STATUS_SKIPPED, self::STATUS_EXCLUDED, self::STATUS_UNSUBSCRIBED, self::STATUS_BLOCKED => 'gray',
            default => 'info',
        };
    }

    public function domain(): string
    {
        $parts = explode('@', $this->email);

        return strtolower(end($parts) ?: '');
    }
}
