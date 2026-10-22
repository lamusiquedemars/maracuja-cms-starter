<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

final class SiteManifest
{
    private const GALLERY_PRESETS = [
        'grid' => 'Grille',
        'featured' => 'Mise en avant',
        'carousel' => 'Carrousel',
    ];
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

    /** @return array{theme: string, gallery_preset: ?string} */
    public static function presentation(): array
    {
        if (! config('maracuja.module_manifest.enabled', false)) {
            return [
                'theme' => (string) config('maracuja.theme', 'default'),
                'gallery_preset' => Modules::enabled('gallery') ? (string) config('maracuja.gallery.layout', 'grid') : null,
            ];
        }

        $presentation = self::read()['presentation'] ?? [];
        $theme = is_array($presentation) ? $presentation['theme'] ?? null : null;
        $galleryPreset = is_array($presentation) ? $presentation['gallery_preset'] ?? null : null;

        if (! is_string($theme) || $theme === '') {
            $theme = (string) config('maracuja.theme', 'default');
        }

        if (! is_string($galleryPreset) || ! array_key_exists($galleryPreset, self::GALLERY_PRESETS)) {
            $galleryPreset = self::declares('gallery') ? (string) config('maracuja.gallery.layout', 'grid') : null;
        }

        return ['theme' => $theme, 'gallery_preset' => $galleryPreset];
    }

    public static function galleryPresetLabel(?string $preset): ?string
    {
        return $preset === null ? null : self::GALLERY_PRESETS[$preset] ?? null;
    }
}
