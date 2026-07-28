<?php

namespace App\Modules\Contacts\Models;

use App\Modules\Audience\Models\AudienceContact;
use App\Modules\Inquiries\Models\Inquiry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contact extends Model
{
    protected $fillable = [
        'first_name',
        'last_name',
        'display_name',
        'organization_name',
        'email',
        'normalized_email',
        'phone',
        'normalized_phone',
        'locale',
        'country_code',
        'source',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $contact): void {
            $contact->email = self::clean($contact->email);
            $contact->normalized_email = $contact->email !== null
                ? mb_strtolower($contact->email)
                : null;
            $contact->phone = self::clean($contact->phone);
            $contact->normalized_phone = $contact->phone !== null
                ? preg_replace('/[^\d+]/', '', $contact->phone)
                : null;
        });
    }

    public function audienceContacts(): HasMany
    {
        return $this->hasMany(AudienceContact::class);
    }

    public function inquiries(): HasMany
    {
        return $this->hasMany(Inquiry::class);
    }

    private static function clean(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}
