<?php

namespace App\Console\Commands;

use App\Modules\Acquisition\Models\AcquisitionReportingSnapshot;
use App\Modules\Acquisition\Services\CremonaReportingClient;
use Illuminate\Console\Command;
use Throwable;

class SyncAcquisitionSummaryCommand extends Command
{
    protected $signature = 'acquisition:sync-summary';

    protected $description = 'Met à jour le résumé de campagnes affiché dans le tableau de bord du site.';

    public function handle(CremonaReportingClient $client): int
    {
        $config = config('maracuja.acquisition.cremona');

        if (! ($config['enabled'] ?? false) || blank($config['reporting_endpoint'] ?? null)
            || blank($config['token'] ?? null) || blank($config['site_reference'] ?? null)) {
            $this->components->warn('La connexion de reporting Cremona n’est pas configurée.');

            return self::SUCCESS;
        }

        try {
            $siteReference = (string) $config['site_reference'];
            $payload = $client->summary($siteReference);
            AcquisitionReportingSnapshot::query()->updateOrCreate(
                ['site_reference' => $siteReference],
                ['payload' => $payload, 'fetched_at' => now(), 'last_error' => null],
            );
            $this->components->info('Résumé de campagnes mis à jour.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            AcquisitionReportingSnapshot::query()->where('site_reference', $config['site_reference'])->update([
                'last_error' => mb_substr($exception->getMessage(), 0, 2000),
            ]);
            $this->components->error('Impossible de mettre à jour le résumé de campagnes.');

            return self::FAILURE;
        }
    }
}
