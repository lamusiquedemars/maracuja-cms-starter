<?php

namespace App\Providers;

use App\Console\Commands\SyncAcquisitionSummaryCommand;
use App\Models\User;
use App\Modules\Conversations\Console\Commands\PruneConversationsCommand;
use App\Modules\Conversations\Contracts\ConversationAiProvider;
use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Policies\ConversationPolicy;
use App\Modules\Conversations\Providers\FakeConversationAiProvider;
use App\Modules\Conversations\Providers\OpenAiConversationProvider;
use App\Modules\Media\Policies\MediaAssetPolicy;
use Maracuja\SiteCore\Foundation\Models\SiteSetting;
use App\Support\ContentPermissions;
use App\Support\Audience\ReferenceSiteAudienceIdentityLinker;
use App\Support\FoundationAccessGate;
use App\Support\FoundationModuleAvailability;
use App\Support\FoundationPublicPageUrlGenerator;
use App\Support\ReferenceSiteLeadReceiver;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use RuntimeException;
use Maracuja\SiteCore\Media\Models\MediaAsset;
use Maracuja\SiteCore\Foundation\Contracts\FoundationAccessGate as FoundationAccessGateContract;
use Maracuja\SiteCore\Foundation\Contracts\ModuleAvailability;
use Maracuja\SiteCore\Foundation\Contracts\SiteModuleRegistry;
use Maracuja\SiteCore\Foundation\Contracts\PublicPageUrlGenerator;
use Maracuja\SiteCore\Contact\Contracts\LeadReceiver;
use App\Support\ReferenceSiteModuleRegistry;
use App\Modules\Contacts\Actions\ResolveContact;
use App\Modules\Contacts\Models\Contact;
use Maracuja\SiteCore\Audience\Contracts\AudienceIdentityLinker;
use Maracuja\SiteCore\Audience\Models\AudienceContact;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(FoundationAccessGateContract::class, FoundationAccessGate::class);
        $this->app->singleton(ModuleAvailability::class, FoundationModuleAvailability::class);
        $this->app->singleton(SiteModuleRegistry::class, ReferenceSiteModuleRegistry::class);
        $this->app->singleton(PublicPageUrlGenerator::class, FoundationPublicPageUrlGenerator::class);
        $this->app->singleton(LeadReceiver::class, ReferenceSiteLeadReceiver::class);
        $this->app->singleton(AudienceIdentityLinker::class, ReferenceSiteAudienceIdentityLinker::class);
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
            SyncAcquisitionSummaryCommand::class,
        ]);

        $moduleMigrationPaths = [
            app_path('Modules/Inquiries/database/migrations'),
            app_path('Modules/Appointments/database/migrations'),
            app_path('Modules/Contacts/database/migrations'),
            app_path('Modules/Conversations/database/migrations'),
            database_path('migrations/audience-legacy'),
            app_path('Modules/Acquisition/database/migrations'),
            app_path('Modules/Media/database/migrations'),
        ];

        foreach ($moduleMigrationPaths as $path) {
            if (is_dir($path)) {
                $this->loadMigrationsFrom($path);
            }
        }

        Gate::policy(MediaAsset::class, MediaAssetPolicy::class);
        Gate::policy(Conversation::class, ConversationPolicy::class);
        $this->registerAudienceContactBridge();
        $this->registerRoleGate();
    }

    private function registerAudienceContactBridge(): void
    {
        AudienceContact::resolveRelationUsing('contact', fn (AudienceContact $contact) => $contact->belongsTo(Contact::class, 'contact_id'));

        AudienceContact::saving(function (AudienceContact $audienceContact): void {
            $contact = ResolveContact::run([
                'first_name' => $audienceContact->first_name,
                'last_name' => $audienceContact->last_name,
                'organization_name' => $audienceContact->organization_name,
                'email' => $audienceContact->email,
                'source' => 'audience',
            ]);

            $audienceContact->setAttribute('contact_id', $contact->getKey());
        });
    }

    private function registerRoleGate(): void
    {
        Gate::before(function (User $user, string $ability, array $arguments): ?bool {
            $subject = $arguments[0] ?? null;
            $subjectClass = is_object($subject) ? $subject::class : $subject;

            if (! ContentPermissions::allows($user, $subjectClass)) {
                return false;
            }

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
