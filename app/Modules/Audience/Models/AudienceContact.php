<?php

namespace App\Modules\Audience\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class AudienceContact extends Model
{
    protected $fillable = [
        'first_name',
        'last_name',
        'organization_name',
        'email',
        'notes',
        'accepts_email',
        'unsubscribe_token',
        'unsubscribed_at',
        'last_contacted_at',
        'brevo_synced_at',
        'brevo_sync_status',
        'brevo_sync_error',
        'email_blacklisted_at',
        'hard_bounced_at',
        'last_bounce_reason',
    ];

    protected static function booted(): void
    {
        static::creating(function (AudienceContact $contact): void {
            if (! $contact->unsubscribe_token) {
                $contact->unsubscribe_token = Str::random(48);
            }
        });

        static::saving(function (AudienceContact $contact): void {
            if ($contact->isDirty('unsubscribed_at') && $contact->unsubscribed_at !== null) {
                $contact->accepts_email = false;

                return;
            }

            if ($contact->isDirty('accepts_email') && $contact->accepts_email) {
                $contact->unsubscribed_at = null;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'accepts_email' => 'boolean',
            'unsubscribed_at' => 'datetime',
            'last_contacted_at' => 'datetime',
            'brevo_synced_at' => 'datetime',
            'email_blacklisted_at' => 'datetime',
            'hard_bounced_at' => 'datetime',
        ];
    }

    public function segments(): BelongsToMany
    {
        return $this->belongsToMany(AudienceSegment::class, 'audience_contact_segment');
    }

    public function canReceiveSegmentEmail(): bool
    {
        return $this->accepts_email
            && $this->unsubscribed_at === null
            && $this->hard_bounced_at === null
            && $this->email_blacklisted_at === null;
    }

    public function unsubscribe(): void
    {
        $this->forceFill([
            'accepts_email' => false,
            'unsubscribed_at' => $this->unsubscribed_at ?? now(),
        ])->save();
    }
}
