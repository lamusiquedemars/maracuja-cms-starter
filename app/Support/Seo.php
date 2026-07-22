<?php

namespace App\Support;

use App\Modules\SiteSettings\Models\SiteSetting;
use Illuminate\Support\Str;

class Seo
{
    /**
     * @param  array{title?: ?string, description?: ?string, image?: ?string, type?: ?string, canonical?: ?string}  $data
     * @return array{title: string, description: string, canonical: string, image: ?string, type: string, robots: string, site_name: string}
     */
    public static function make(SiteSetting $settings, array $data = []): array
    {
        $title = $data['title'] ?: $settings->default_seo_title ?: $settings->site_name;
        $description = $data['description'] ?: $settings->default_seo_description ?: $settings->baseline ?: '';
        $image = $data['image'] ?: $settings->defaultOgImageUrl();

        return [
            'title' => Str::limit($title, 70, ''),
            'description' => Str::limit($description, 165, ''),
            'canonical' => self::absoluteUrl($data['canonical'] ?? url()->current()),
            'image' => $image ? self::absoluteUrl($image) : null,
            'type' => $data['type'] ?: 'website',
            'robots' => config('maracuja.seo.indexable') ? 'index, follow' : 'noindex, nofollow',
            'site_name' => $settings->site_name,
        ];
    }

    public static function absoluteUrl(string $url): string
    {
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        return url($url);
    }
}
