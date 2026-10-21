<?php

declare(strict_types=1);

namespace App\Support;

use Maracuja\SiteCore\Foundation\Contracts\ModuleAvailability;

final class FoundationModuleAvailability implements ModuleAvailability
{
    public function enabled(string $module): bool
    {
        return Modules::enabled($module);
    }
}
