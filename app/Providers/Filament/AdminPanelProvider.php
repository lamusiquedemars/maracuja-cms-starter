<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\AdminOverview;
use App\Filament\Widgets\AdminWelcome;
use App\Support\Modules;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Maracuja\SiteCore\Foundation\Models\SiteSetting;
use Maracuja\SiteCore\Gallery\Filament\Resources\Galleries\GalleryResource;
use Maracuja\SiteCore\Media\Filament\Resources\MediaAssets\MediaAssetResource;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $panel = $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName(fn (): string => Schema::hasTable('site_settings')
                ? SiteSetting::query()->value('site_name') ?: config('maracuja.product_name', 'Maracuja CMS')
                : config('maracuja.product_name', 'Maracuja CMS'))
            ->brandLogo(fn (): ?string => Schema::hasTable('site_settings')
                ? SiteSetting::query()->first()?->logoUrl()
                : null)
            ->brandLogoHeight('2.5rem')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverResources(in: base_path('vendor/maracuja-digital/site-core-foundation/src/Filament/Resources'), for: 'Maracuja\SiteCore\Foundation\Filament\Resources')
            ->discoverResources(in: base_path('vendor/maracuja-digital/site-core-publication/src/Filament/Resources'), for: 'Maracuja\SiteCore\Publication\Filament\Resources')
            ->discoverResources(in: base_path('vendor/maracuja-digital/site-core-events/src/Filament/Resources'), for: 'Maracuja\SiteCore\Events\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                AdminWelcome::class,
                AdminOverview::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);

        if (Modules::enabled('inquiries') && is_dir(app_path('Modules/Inquiries/Filament/Resources'))) {
            $panel->discoverResources(
                in: app_path('Modules/Inquiries/Filament/Resources'),
                for: 'App\Modules\Inquiries\Filament\Resources',
            );
        }

        if (Modules::enabled('contacts') && is_dir(app_path('Modules/Contacts/Filament/Resources'))) {
            $panel->discoverResources(
                in: app_path('Modules/Contacts/Filament/Resources'),
                for: 'App\Modules\Contacts\Filament\Resources',
            );
        }

        if (is_dir(app_path('Modules/Conversations/Filament/Resources'))) {
            $panel->discoverResources(
                in: app_path('Modules/Conversations/Filament/Resources'),
                for: 'App\Modules\Conversations\Filament\Resources',
            );
        }

        if (Modules::enabled('appointments') && is_dir(app_path('Modules/Appointments/Filament/Resources'))) {
            $panel->discoverResources(
                in: app_path('Modules/Appointments/Filament/Resources'),
                for: 'App\Modules\Appointments\Filament\Resources',
            );
        }

        if (Modules::enabled('audience')
            && config('maracuja-audience.use_legacy_resources', true)
            && is_dir(app_path('Modules/Audience/Filament/Resources'))) {
            $panel->discoverResources(
                in: app_path('Modules/Audience/Filament/Resources'),
                for: 'App\Modules\Audience\Filament\Resources',
            );
        }

        if (Modules::enabled('audience') && ! config('maracuja-audience.use_legacy_resources', true)) {
            $panel->discoverResources(
                in: base_path('vendor/maracuja-digital/site-core-audience/src/Filament/Resources'),
                for: 'Maracuja\SiteCore\Audience\Filament\Resources',
            );
        }

        if (Modules::enabled('brevo')) {
            $panel->discoverResources(
                in: base_path('vendor/maracuja-digital/site-core-brevo/src/Filament/Resources'),
                for: 'Maracuja\SiteCore\Brevo\Filament\Resources',
            );
        }

        if (Modules::enabled('acquisition') && is_dir(app_path('Modules/Acquisition/Filament/Resources'))) {
            $panel->discoverResources(
                in: app_path('Modules/Acquisition/Filament/Resources'),
                for: 'App\Modules\Acquisition\Filament\Resources',
            );
            $panel->discoverWidgets(in: app_path('Modules/Acquisition/Filament/Widgets'), for: 'App\Modules\Acquisition\Filament\Widgets');
        }

        $panel->resources(array_filter([
            MediaAssetResource::class,
            Modules::enabled('gallery') ? GalleryResource::class : null,
        ]));

        return $panel;
    }
}
