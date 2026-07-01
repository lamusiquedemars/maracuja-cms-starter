<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaFiles
{
    /** @return array<string, string> */
    public static function options(string $directory): array
    {
        return collect(Storage::disk('public')->files($directory))
            ->filter(fn (string $path): bool => self::isImagePath($path))
            ->sort()
            ->mapWithKeys(function (string $path): array {
                $dimensions = self::dimensions($path);
                $label = basename($path);

                if ($dimensions) {
                    $label .= " ({$dimensions['width']} x {$dimensions['height']})";
                }

                return [$path => $label];
            })
            ->all();
    }

    public static function isAllowed(string $path, string $directory): bool
    {
        return self::isStoragePath($path)
            && Str::startsWith($path, trim($directory, '/').'/')
            && Storage::disk('public')->exists($path)
            && self::isImagePath($path);
    }

    public static function url(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', '/'])) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }

    /** @return array{width: int, height: int}|null */
    public static function dimensions(?string $path): ?array
    {
        if (blank($path)) {
            return null;
        }

        $absolutePath = self::absolutePath($path);

        if (! $absolutePath || ! is_file($absolutePath)) {
            return null;
        }

        $size = @getimagesize($absolutePath);

        if (! is_array($size)) {
            return null;
        }

        return [
            'width' => (int) $size[0],
            'height' => (int) $size[1],
        ];
    }

    public static function isStoragePath(string $path): bool
    {
        return ! Str::startsWith($path, ['http://', 'https://', '/']);
    }

    private static function absolutePath(string $path): ?string
    {
        if (Str::startsWith($path, '/')) {
            return public_path(ltrim($path, '/'));
        }

        return Storage::disk('public')->path($path);
    }

    private static function isImagePath(string $path): bool
    {
        return Str::of($path)->lower()->endsWith(['.jpg', '.jpeg', '.png', '.webp', '.gif', '.svg']);
    }
}
