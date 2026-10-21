<?php

namespace App\Support;

use Maracuja\SiteCore\Events\Models\Event;
use Maracuja\SiteCore\Events\Models\Venue;
use Maracuja\SiteCore\Audience\Models\AudienceContact;
use Maracuja\SiteCore\Brevo\Models\AudienceBrevoSetting;

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

        if (config('maracuja.module_manifest.enabled', false)) {
            return SiteManifest::declares($module);
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
            'venues' => class_exists(Venue::class),
            'events' => class_exists(Event::class),
            'gallery' => app_path('Modules/Gallery'),
            'contact_form' => app_path('Modules/ContactForm'),
            'inquiries' => app_path('Modules/Inquiries'),
            'appointments' => app_path('Modules/Appointments'),
            'contacts' => app_path('Modules/Contacts'),
            'conversations' => app_path('Modules/Conversations'),
            // Ces capacités sont fournies par des packages du Site Core : une
            // installation client ne doit pas simuler leur présence par un
            // dossier applicatif vide.
            'audience' => class_exists(AudienceContact::class),
            'brevo' => class_exists(AudienceBrevoSetting::class),
            'acquisition' => app_path('Modules/Acquisition'),
            'campaigns' => app_path('Modules/Campaigns'),
        ];

        $installation = $paths[$module] ?? app_path('Modules/'.str($module)->studly());

        return is_bool($installation) ? $installation : is_dir($installation);
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
