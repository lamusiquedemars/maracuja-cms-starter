<?php

namespace App\Providers;

use App\Modules\Conversations\Console\Commands\PruneConversationsCommand;
use App\Modules\Conversations\Contracts\ConversationAiProvider;
use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Policies\ConversationPolicy;
use App\Modules\Conversations\Providers\FakeConversationAiProvider;
use App\Modules\Conversations\Providers\OpenAiConversationProvider;
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
        $this->app->bind(ConversationAiProvider::class, function (): ConversationAiProvider {
            return match (config('maracuja.conversations.ai.provider')) {
                'fake' => new FakeConversationAiProvider,
                'openai' => new OpenAiConversationProvider,
                default => throw new \InvalidArgumentException('Unsupported conversation AI provider.'),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->commands([
            PruneConversationsCommand::class,
        ]);

        $moduleMigrationPaths = [
            app_path('Modules/Inquiries/database/migrations'),
            app_path('Modules/Appointments/database/migrations'),
            app_path('Modules/Contacts/database/migrations'),
            app_path('Modules/Conversations/database/migrations'),
            app_path('Modules/Audience/database/migrations'),
            app_path('Modules/Media/database/migrations'),
        ];

        foreach ($moduleMigrationPaths as $path) {
            if (is_dir($path)) {
                $this->loadMigrationsFrom($path);
            }
        }

        Gate::policy(MediaAsset::class, MediaAssetPolicy::class);
        Gate::policy(Conversation::class, ConversationPolicy::class);
    }
}
