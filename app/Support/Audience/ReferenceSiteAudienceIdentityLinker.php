<?php

namespace App\Support\Audience;

use Maracuja\SiteCore\Audience\Contracts\AudienceIdentityLinker;
use Maracuja\SiteCore\Audience\Models\AudienceContact;

final class ReferenceSiteAudienceIdentityLinker implements AudienceIdentityLinker
{
    public function link(AudienceContact $contact, array $attributes): ?string
    {
        $contactId = $contact->getAttribute('contact_id');

        return $contactId === null ? null : 'contact:'.$contactId;
    }
}
