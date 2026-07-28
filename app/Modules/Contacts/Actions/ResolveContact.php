<?php

namespace App\Modules\Contacts\Actions;

use App\Modules\Contacts\Models\Contact;

class ResolveContact
{
    /**
     * @param  array{
     *     first_name?: ?string,
     *     last_name?: ?string,
     *     display_name?: ?string,
     *     organization_name?: ?string,
     *     email?: ?string,
     *     phone?: ?string,
     *     locale?: ?string,
     *     country_code?: ?string,
     *     source?: ?string
     * }  $attributes
     */
    public static function run(array $attributes): Contact
    {
        $email = self::clean($attributes['email'] ?? null);
        $normalizedEmail = $email !== null ? mb_strtolower($email) : null;
        $phone = self::clean($attributes['phone'] ?? null);
        $normalizedPhone = self::normalizePhone($phone);

        $contact = null;

        if ($normalizedEmail !== null) {
            $contact = Contact::query()->where('normalized_email', $normalizedEmail)->first();
        }

        if ($contact === null && $normalizedPhone !== null) {
            $contact = Contact::query()->where('normalized_phone', $normalizedPhone)->first();
        }

        $values = [
            'first_name' => self::clean($attributes['first_name'] ?? null),
            'last_name' => self::clean($attributes['last_name'] ?? null),
            'display_name' => self::clean($attributes['display_name'] ?? null),
            'organization_name' => self::clean($attributes['organization_name'] ?? null),
            'email' => $email,
            'normalized_email' => $normalizedEmail,
            'phone' => $phone,
            'normalized_phone' => $normalizedPhone,
            'locale' => self::clean($attributes['locale'] ?? null),
            'country_code' => self::clean($attributes['country_code'] ?? null),
            'source' => self::clean($attributes['source'] ?? null),
        ];

        if ($contact === null) {
            return Contact::query()->create($values);
        }

        $contact->fill(array_filter(
            $values,
            fn (mixed $value, string $key): bool => $value !== null && blank($contact->getAttribute($key)),
            ARRAY_FILTER_USE_BOTH,
        ))->save();

        return $contact;
    }

    private static function clean(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    private static function normalizePhone(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $normalized = preg_replace('/[^\d+]/', '', $phone);

        return filled($normalized) ? $normalized : null;
    }
}
