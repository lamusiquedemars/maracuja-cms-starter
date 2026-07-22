<?php

namespace App\Console\Commands;

use App\Modules\Media\Services\MediaAuditService;
use Illuminate\Console\Command;

class MaracujaMediaAuditCommand extends Command
{
    protected $signature = 'maracuja:media:audit
        {--json : Retourne le rapport complet au format JSON}
        {--no-database : Ignore temporairement les références en base de données}';

    protected $description = 'Audite les stockages, doublons et références médias sans modifier aucun fichier.';

    public function handle(MediaAuditService $auditService): int
    {
        $report = $auditService->audit(! (bool) $this->option('no-database'));

        if ($this->option('json')) {
            $this->line((string) json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            return $report['summary']['anomalies'] > 0 ? self::FAILURE : self::SUCCESS;
        }

        $this->info('Audit des médias Maracuja');
        $this->newLine();
        $this->table(['Mesure', 'Total'], [
            ['Fichiers physiques', $report['summary']['files']],
            ['Poids total', $this->formatBytes($report['summary']['bytes'])],
            ['Groupes de doublons', $report['summary']['duplicate_groups']],
            ['Références détectées', $report['summary']['references']],
            ['Anomalies', $report['summary']['anomalies']],
        ]);

        if ($report['duplicates'] !== []) {
            $this->newLine();
            $this->warn('Doublons par contenu');
            $this->table(
                ['SHA-256', 'Poids', 'Emplacements'],
                array_map(fn (array $duplicate): array => [
                    substr($duplicate['sha256'], 0, 12).'…',
                    $this->formatBytes($duplicate['size']),
                    implode("\n", $duplicate['paths']),
                ], $report['duplicates'])
            );
        }

        if ($report['anomalies'] !== []) {
            $this->newLine();
            $this->error('Anomalies à traiter');
            $this->table(
                ['Type', 'Emplacement', 'Détail'],
                array_map(fn (array $anomaly): array => [
                    $anomaly['type'],
                    $anomaly['location'],
                    $anomaly['detail'],
                ], $report['anomalies'])
            );

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Le stockage média respecte le contrat Maracuja.');

        return self::SUCCESS;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' o';
        }

        if ($bytes < 1024 * 1024) {
            return number_format($bytes / 1024, 1, ',', ' ').' Ko';
        }

        return number_format($bytes / (1024 * 1024), 1, ',', ' ').' Mo';
    }
}
