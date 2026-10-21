<?php

declare(strict_types=1);

namespace App\Support;

use Maracuja\SiteCore\Foundation\Contracts\SiteModuleRegistry;
use Maracuja\SiteCore\Foundation\Data\SiteModuleDefinition;
use Maracuja\SiteCore\Foundation\Support\ConfiguredSiteModuleRegistry;

/**
 * The Core owns the catalogue; this site supplies its actual release state.
 */
final class ReferenceSiteModuleRegistry implements SiteModuleRegistry
{
    public function __construct(private readonly ConfiguredSiteModuleRegistry $catalogue) {}

    public function all(): array
    {
        return $this->catalogue->all();
    }

    public function enabled(string $key): bool
    {
        return Modules::enabled($key);
    }
}
