<?php

namespace App\Modules\Audience\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AudienceBrevoEvent extends Model
{
    protected $fillable = [
        'segment_message_id',
        'segment_message_delivery_id',
        'audience_contact_id',
        'brevo_campaign_id',
        'email',
        'event_type',
        'event_date',
        'raw_payload',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'brevo_campaign_id' => 'integer',
            'event_date' => 'datetime',
            'raw_payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public function segmentMessage(): BelongsTo
    {
        return $this->belongsTo(SegmentMessage::class);
    }

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(SegmentMessageDelivery::class, 'segment_message_delivery_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(AudienceContact::class, 'audience_contact_id');
    }
}
