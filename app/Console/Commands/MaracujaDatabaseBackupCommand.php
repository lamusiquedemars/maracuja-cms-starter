<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class MaracujaDatabaseBackupCommand extends Command
{
    protected $signature = 'maracuja:db:backup {--name= : Nom explicite du snapshot}';

    protected $description = 'Crée un snapshot logique privé de toutes les données de la base courante.';

    public function handle(): int
    {
        $name = filled($this->option('name'))
            ? Str::slug((string) $this->option('name'))
            : now()->format('Ymd-His');
        $directory = storage_path('app/private/database-backups');
        $path = $directory.DIRECTORY_SEPARATOR.$name.'.json';

        if (! is_dir($directory) && ! mkdir($directory, 0750, true) && ! is_dir($directory)) {
            throw new RuntimeException("Impossible de créer {$directory}.");
        }
        if (is_file($path)) {
            $this->error("Le snapshot existe déjà : {$path}");

            return self::FAILURE;
        }

        $tables = collect(DB::select(
            'select table_name from information_schema.tables where table_schema = database() and table_type = ?',
            ['BASE TABLE'],
        ))->map(fn (object $row): string => (string) ($row->TABLE_NAME ?? $row->table_name))
            ->sort()
            ->values();

        $snapshot = [
            'version' => 1,
            'database' => DB::connection()->getDatabaseName(),
            'created_at' => now()->toIso8601String(),
            'tables' => $tables->mapWithKeys(fn (string $table): array => [$table => [
                'columns' => Schema::getColumnListing($table),
                'rows' => DB::table($table)->orderBy(Schema::hasColumn($table, 'id') ? 'id' : Schema::getColumnListing($table)[0])->get()->map(fn (object $row): array => (array) $row)->all(),
            ]])->all(),
        ];
        $json = json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        if (file_put_contents($path, $json."\n", LOCK_EX) === false) {
            throw new RuntimeException("Impossible d’écrire {$path}.");
        }

        $this->info('Snapshot de base créé sans modifier les données.');
        $this->line('Base : '.$snapshot['database']);
        $this->line('Tables : '.$tables->count());
        $this->line('Fichier privé : '.$path);

        return self::SUCCESS;
    }
}
