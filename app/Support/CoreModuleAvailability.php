<?php

namespace App\Support;

use Maracuja\SiteCore\Foundation\Contracts\ModuleAvailability;

final class CoreModuleAvailability implements ModuleAvailability
{
    public function enabled(string $module): bool
    {
        return Modules::enabled($module);
    }
}
