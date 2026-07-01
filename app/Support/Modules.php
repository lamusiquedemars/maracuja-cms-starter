<?php

namespace App\Support;

class Modules
{
    public static function enabled(string $module): bool
    {
        $module = match ($module) {
            'contact' => 'contact_form',
            default => $module,
        };

        if (! self::installed($module)) {
            return false;
        }

        return (bool) config("maracuja.modules.{$module}", false);
    }

    public static function installed(string $module): bool
    {
        $paths = [
            'site_settings' => app_path('Modules/SiteSettings'),
            'notices' => app_path('Modules/Notices'),
            'content_slots' => app_path('Modules/ContentSlots'),
            'pages' => app_path('Modules/Pages'),
            'news' => app_path('Modules/News'),
            'articles' => app_path('Modules/Articles'),
            'venues' => app_path('Modules/Venues'),
            'events' => app_path('Modules/Events'),
            'gallery' => app_path('Modules/Gallery'),
            'contact_form' => app_path('Modules/ContactForm'),
            'inquiries' => app_path('Modules/Inquiries'),
            'audience' => app_path('Modules/Audience'),
            'campaigns' => app_path('Modules/Campaigns'),
        ];

        return is_dir($paths[$module] ?? app_path('Modules/'.str($module)->studly()));
    }

    public static function offer(): string
    {
        return (string) config('maracuja.offer', 'signature');
    }

    public static function developerToolEnabled(string $tool): bool
    {
        return (bool) config("maracuja.developer_tools.{$tool}", false);
    }
}
