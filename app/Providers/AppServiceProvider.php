<?php

namespace App\Providers;

use App\Modules\Media\Models\MediaAsset;
use App\Modules\Media\Policies\MediaAssetPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $moduleMigrationPaths = [
            app_path('Modules/Inquiries/database/migrations'),
            app_path('Modules/Audience/database/migrations'),
            app_path('Modules/Media/database/migrations'),
        ];

        foreach ($moduleMigrationPaths as $path) {
            if (is_dir($path)) {
                $this->loadMigrationsFrom($path);
            }
        }

        Gate::policy(MediaAsset::class, MediaAssetPolicy::class);
    }
}
