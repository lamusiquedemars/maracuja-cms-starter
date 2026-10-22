<?php

namespace App\Modules\Inquiries\Models;

use App\Modules\Appointments\Enums\AppointmentStatus;
use App\Modules\Conversations\Models\Conversation;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Maracuja\SiteCore\Contact\Models\Inquiry as CoreInquiry;

/** Site adapter: conversations and appointment booking are not Core concerns. */
class Inquiry extends CoreInquiry
{
    protected $fillable = [
        'contact_id',
        'conversation_id',
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'consent_at',
        'source',
        'attribution_source',
        'attribution_medium',
        'attribution_campaign',
        'attribution_first_touch',
        'attribution_last_touch',
        'attribution_method',
        'attribution_confidence',
        'status',
        'appointment_status',
        'booking_opened_at',
        'scheduled_start_at',
        'scheduled_end_at',
        'appointment_timezone',
        'appointment_external_reference',
        'internal_notes',
        'read_at',
        'handled_at',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'appointment_status' => AppointmentStatus::class,
            'booking_opened_at' => 'datetime',
            'scheduled_start_at' => 'datetime',
            'scheduled_end_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

}
