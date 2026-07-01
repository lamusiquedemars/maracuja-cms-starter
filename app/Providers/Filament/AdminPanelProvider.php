<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\AdminOverview;
use App\Filament\Widgets\AdminWelcome;
use App\Modules\SiteSettings\Models\SiteSetting;
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
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
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

        if (Modules::enabled('audience') && is_dir(app_path('Modules/Audience/Filament/Resources'))) {
            $panel->discoverResources(
                in: app_path('Modules/Audience/Filament/Resources'),
                for: 'App\Modules\Audience\Filament\Resources',
            );
        }

        return $panel;
    }
}
