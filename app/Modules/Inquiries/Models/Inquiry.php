<?php

namespace App\Modules\Inquiries\Models;

use App\Modules\Appointments\Enums\AppointmentStatus;
use App\Modules\Contacts\Actions\ResolveContact;
use App\Modules\Contacts\Models\Contact;
use App\Modules\Conversations\Models\Conversation;
use App\Modules\Inquiries\Enums\InquiryStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inquiry extends Model
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
            'status' => InquiryStatus::class,
            'appointment_status' => AppointmentStatus::class,
            'booking_opened_at' => 'datetime',
            'scheduled_start_at' => 'datetime',
            'scheduled_end_at' => 'datetime',
            'consent_at' => 'datetime',
            'read_at' => 'datetime',
            'handled_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $inquiry): void {
            $inquiry->contact_id = ResolveContact::run([
                'display_name' => $inquiry->name,
                'email' => $inquiry->email,
                'phone' => $inquiry->phone,
                'source' => 'inquiry',
            ])->getKey();
        });
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function markRead(): void
    {
        if ($this->read_at === null) {
            $this->forceFill([
                'read_at' => now(),
            ])->save();
        }
    }

    public function moveTo(InquiryStatus $status): void
    {
        $updates = [
            'status' => $status,
        ];

        if ($this->read_at === null) {
            $updates['read_at'] = now();
        }

        if ($status === InquiryStatus::Handled) {
            $updates['handled_at'] = now();
        }

        if ($status === InquiryStatus::Archived) {
            $updates['archived_at'] = now();
        }

        $this->update($updates);
    }

    public function markWaitingCustomer(): void
    {
        $this->moveTo(InquiryStatus::WaitingCustomer);
    }
}
