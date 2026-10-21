<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

final class SiteManifest
{
    /** @return array<string, mixed> */
    public static function read(): array
    {
        $contents = file_get_contents(base_path('maracuja-site.manifest.json'));
        $manifest = is_string($contents) ? json_decode($contents, true) : null;

        if (! is_array($manifest) || ($manifest['schema_version'] ?? null) !== 1 || ! is_array($manifest['modules'] ?? null)) {
            throw new RuntimeException('Invalid Maracuja Site manifest.');
        }

        return $manifest;
    }

    public static function declares(string $module): bool
    {
        $definition = self::read()['modules'][$module] ?? null;

        return is_array($definition) && ($definition['enabled'] ?? false) === true;
    }
}
