<?php

namespace App\Support\Audience;

use App\Modules\Inquiries\Models\Inquiry;
use Maracuja\SiteCore\Audience\Models\AudienceContact;

final class CreateContactFromInquiry
{
    /** @return array{contact: AudienceContact, created: bool} */
    public static function run(Inquiry $inquiry): array
    {
        $contact = AudienceContact::query()->firstOrNew(['email' => mb_strtolower($inquiry->email)]);
        $created = ! $contact->exists;

        $contact->fill([
            'first_name' => $contact->first_name ?: $inquiry->name,
            'notes' => $contact->notes ?: $inquiry->message,
        ]);
        $contact->save();

        return compact('contact', 'created');
    }
}
