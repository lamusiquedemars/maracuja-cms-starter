<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Modules\SiteSettings\Models\SiteSetting;
use App\Support\Modules;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MaracujaDoctorCommand extends Command
{
    protected $signature = 'maracuja:doctor {--production : Lance des contrôles plus stricts avant livraison}';

    protected $description = 'Vérifie que l’installation Maracuja CMS est prête pour la livraison.';

    /** @var array<int, array{status: string, check: string, detail: string}> */
    private array $results = [];

    public function handle(): int
    {
        $production = (bool) $this->option('production');

        $this->info('Diagnostic Maracuja CMS');
        $this->newLine();

        $this->checkAppConfiguration($production);
        $this->checkOfferConfiguration();
        $this->checkDatabaseContent();
        $this->checkStorage();
        $this->checkSeo($production);

        $this->table(['Statut', 'Contrôle', 'Détail'], $this->results);

        if ($this->hasFailures()) {
            $this->newLine();
            $this->error('L’installation demande une vérification avant livraison.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('L’installation semble saine.');

        return self::SUCCESS;
    }

    private function checkAppConfiguration(bool $production): void
    {
        $this->record(
            filled(config('app.key')) ? 'ok' : 'fail',
            'APP_KEY',
            filled(config('app.key')) ? 'La clé d’application est configurée.' : 'Lancez php artisan key:generate.'
        );

        $url = (string) config('app.url');
        $isLocalUrl = str_contains($url, 'localhost') || str_contains($url, '127.0.0.1');

        $this->record(
            $production && $isLocalUrl ? 'fail' : 'ok',
            'APP_URL',
            $production && $isLocalUrl ? 'L’URL de production pointe encore vers le local.' : "URL actuelle : {$url}"
        );
    }

    private function checkOfferConfiguration(): void
    {
        $offer = Modules::offer();
        $offers = array_keys((array) config('maracuja.offers', []));

        $this->record(
            in_array($offer, $offers, true) ? 'ok' : 'fail',
            'Profil d’offre',
            in_array($offer, $offers, true)
                ? "Offre active : {$offer}."
                : 'Offre inconnue. Valeurs attendues : '.implode(', ', $offers).'.'
        );

        $enabledModules = collect((array) config('maracuja.modules', []))
            ->keys()
            ->filter(fn (string $module): bool => Modules::enabled($module))
            ->values()
            ->all();

        $this->record(
            count($enabledModules) > 0 ? 'ok' : 'fail',
            'Modules activés',
            count($enabledModules) > 0 ? implode(', ', $enabledModules) : 'Aucun module n’est activé.'
        );
    }

    private function checkDatabaseContent(): void
    {
        try {
            DB::connection()->getPdo();
        } catch (\Throwable $exception) {
            $this->record('fail', 'Connexion base de données', $exception->getMessage());

            return;
        }

        $this->record('ok', 'Connexion base de données', 'La connexion est disponible.');

        if (! Schema::hasTable('users')) {
            $this->record('fail', 'Migrations', 'La table users est absente. Lancez les migrations.');

            return;
        }

        $this->record('ok', 'Migrations', 'Les tables principales sont disponibles.');

        $this->record(
            User::query()->where('is_admin', true)->exists() ? 'ok' : 'fail',
            'Compte administrateur',
            User::query()->where('is_admin', true)->exists()
                ? 'Au moins un compte administrateur existe.'
                : 'Créez un compte administrateur avant livraison.'
        );

        if (Modules::enabled('site_settings')) {
            $settings = SiteSetting::query()->first();

            $this->record(
                $settings !== null ? 'ok' : 'fail',
                'Paramètres du site',
                $settings !== null ? "Nom du site : {$settings->site_name}." : 'L’enregistrement des paramètres du site est absent.'
            );

            if (Modules::enabled('contact_form')) {
                $this->record(
                    $settings && filled($settings->contact_email) ? 'ok' : 'warn',
                    'Email de contact',
                    $settings && filled($settings->contact_email)
                        ? "Email de contact : {$settings->contact_email}."
                        : 'Le module contact est activé, mais l’email de contact est vide.'
                );
            }
        }
    }

    private function checkStorage(): void
    {
        $this->record(
            is_link(public_path('storage')) || file_exists(public_path('storage')) ? 'ok' : 'warn',
            'Stockage public',
            is_link(public_path('storage')) || file_exists(public_path('storage'))
                ? 'public/storage existe.'
                : 'Créez le dossier public/storage et rendez-le accessible en écriture.'
        );
    }

    private function checkSeo(bool $production): void
    {
        $indexable = (bool) config('maracuja.seo.indexable');

        $this->record(
            $production && ! $indexable ? 'fail' : 'ok',
            'Indexation SEO',
            $indexable
                ? 'Le site peut être indexé.'
                : 'Le site est configuré en noindex. Correct pour un laboratoire ou une préproduction, pas pour une production publique.'
        );
    }

    private function record(string $status, string $check, string $detail): void
    {
        $this->results[] = [
            'status' => strtoupper($status),
            'check' => $check,
            'detail' => $detail,
        ];
    }

    private function hasFailures(): bool
    {
        return collect($this->results)
            ->contains(fn (array $result): bool => $result['status'] === 'FAIL');
    }
}
