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
use App\Modules\SiteSettings\Models\SiteSetting;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

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
        $this->guardTestingDatabase();

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
        $this->registerRoleGate();
    }

    private function registerRoleGate(): void
    {
        Gate::before(function (User $user, string $ability, array $arguments): ?bool {
            $subject = $arguments[0] ?? null;
            $subjectClass = is_object($subject) ? $subject::class : $subject;

            // A viewer is read-only across the entire administration. This
            // must happen before model policies, so a permissive policy on a
            // current or future resource cannot accidentally grant a write.
            if (! $user->isAdministrator()
                && ! $user->canEditContent()
                && ! in_array($ability, ['viewAny', 'view'], true)) {
                return false;
            }

            // Let a resource-specific policy decide first. In particular,
            // conversations must remain undeletable, even to an administrator.
            if ($subjectClass && Gate::getPolicyFor($subjectClass)) {
                return null;
            }

            if ($user->isAdministrator()) {
                return true;
            }

            if ($subjectClass === SiteSetting::class) {
                return false;
            }

            if (in_array($ability, ['viewAny', 'view'], true)) {
                return true;
            }

            return $user->canEditContent();
        });
    }

    private function guardTestingDatabase(): void
    {
        if (! $this->app->environment('testing')) {
            return;
        }

        $connection = (string) config('database.default');
        $database = (string) config("database.connections.{$connection}.database");

        if ($database === ':memory:' || str_ends_with($database, '_testing')) {
            return;
        }

        throw new RuntimeException(
            "Tests blocked: database [{$database}] is not a dedicated testing database. "
            .'Use a database name ending in [_testing].'
        );
    }
}
