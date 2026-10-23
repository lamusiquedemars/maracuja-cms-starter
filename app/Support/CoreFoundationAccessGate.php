<?php

namespace App\Support;

use App\Models\User;
use Maracuja\SiteCore\Foundation\Contracts\FoundationAccessGate;

final class CoreFoundationAccessGate implements FoundationAccessGate
{
    public function allows(string $ability): bool
    {
        $user = auth()->user();

        return $user instanceof User && ($user->isAdministrator() || $user->canEditContent());
    }
}
