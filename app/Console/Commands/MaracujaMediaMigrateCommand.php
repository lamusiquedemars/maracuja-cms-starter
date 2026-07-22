<?php

namespace App\Console\Commands;

use App\Modules\Media\Services\MediaMigrationPlanner;
use Illuminate\Console\Command;

class MaracujaMediaMigrateCommand extends Command
{
    protected $signature = 'maracuja:media:migrate
        {--manifest= : Nom du manifeste}
        {--apply : Applique un manifeste existant sans supprimer les sources}
        {--cleanup : Supprime les copies historiques vérifiées}
        {--rollback : Annule les créations d’un manifeste appliqué}
        {--json : Affiche le plan complet}';
    protected $description = 'Prépare la migration dédupliquée et écrit un manifeste privé sans modifier les médias.';

    public function handle(MediaMigrationPlanner $planner): int
    {
        $operations = collect(['apply', 'cleanup', 'rollback'])->filter(fn (string $option): bool => (bool) $this->option($option));
        if ($operations->count() > 1) {
            $this->error('--apply, --cleanup et --rollback sont incompatibles entre eux.');
            return self::INVALID;
        }

        if ($operations->isNotEmpty()) {
            $manifest = (string) $this->option('manifest');
            if ($manifest === '') {
                $this->error('--manifest est obligatoire pour appliquer ou annuler un plan.');
                return self::INVALID;
            }

            $plan = match ($operations->first()) {
                'apply' => $planner->apply($manifest),
                'cleanup' => $planner->cleanup($manifest),
                'rollback' => $planner->rollback($manifest),
            };
            $this->info(match ($operations->first()) {
                'apply' => 'Manifeste appliqué — les sources historiques sont conservées.',
                'cleanup' => 'Copies historiques vérifiées et supprimées.',
                'rollback' => 'Créations du manifeste annulées.',
            });
            $this->line('Médias concernés : '.count($plan['entries']));

            return self::SUCCESS;
        }

        $plan = $planner->plan();
        $manifest = $planner->writeManifest($plan, $this->option('manifest'));
        $summary = $plan['summary'];

        $this->info('Plan de migration créé — aucun média modifié.');
        $this->table(['Mesure', 'Total'], [
            ['Fichiers physiques', $summary['physical_files']],
            ['Médias uniques à cataloguer', $summary['unique_media']],
            ['Copies en doublon', $summary['duplicate_copies']],
            ['Fichiers exclus', $summary['excluded_files']],
            ['Références en base', $summary['database_references']],
        ]);
        $this->line('Manifeste privé : '.$manifest);
        if ($this->option('json')) {
            $this->line((string) json_encode($plan, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        }
        $this->warn('Plan uniquement : aucune copie, création en base ou suppression.');

        return self::SUCCESS;
    }
}
