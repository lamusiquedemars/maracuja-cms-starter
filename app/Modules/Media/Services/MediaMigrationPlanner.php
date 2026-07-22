<?php

namespace App\Modules\Media\Services;

use App\Modules\Media\Models\MediaAsset;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class MediaMigrationPlanner
{
    /** @var array<string, string> */
    private array $roots;

    public function __construct(?array $roots = null)
    {
        $this->roots = $roots ?? [
            'public' => public_path('storage'),
            'legacy_public' => storage_path('app/public'),
            'private' => storage_path('app/private'),
        ];
    }

    public function plan(): array
    {
        $audit = app(MediaAuditService::class)->audit();
        $eligible = collect($audit['files'])
            ->filter(fn (array $file): bool => $this->isEligible($file))
            ->reject(fn (array $file): bool => $file['scope'] === 'private' && $this->isAllowedPrivatePath($file['relative_path']))
            ->groupBy('sha256');

        $entries = $eligible->map(function ($copies, string $checksum): array {
            $source = $copies->sortBy(fn (array $file): int => match ($file['scope']) {
                'public' => 0, 'legacy_public' => 1, default => 2,
            })->first();
            $type = str_starts_with($source['mime_type'], 'image/') ? 'image' : 'document';

            return [
                'checksum' => $checksum,
                'type' => $type,
                'mime_type' => $source['mime_type'],
                'size' => $source['size'],
                'width' => $source['width'],
                'height' => $source['height'],
                'original_name' => basename($source['relative_path']),
                'source' => $source['scope'].':'.$source['relative_path'],
                'copies' => $copies->map(fn (array $file): string => $file['scope'].':'.$file['relative_path'])->values()->all(),
                'destination' => sprintf('media/%s/%s/%s/%s.%s',
                    $type === 'image' ? 'images' : 'documents', now()->format('Y'), now()->format('m'),
                    Str::ulid(), $this->extensionForMime($source['mime_type'])),
            ];
        })->values();

        $eligibleChecksums = $eligible->keys();
        $excluded = collect($audit['files'])
            ->reject(fn (array $file): bool => $eligibleChecksums->contains($file['sha256']))
            ->map(fn (array $file): array => [
                'source' => $file['scope'].':'.$file['relative_path'],
                'mime_type' => $file['mime_type'],
                'reason' => $file['scope'] === 'private' && $this->isAllowedPrivatePath($file['relative_path'])
                    ? 'private_technical_file' : 'unsupported_media_type',
            ])->values();

        return [
            'version' => 1,
            'project' => basename(base_path()),
            'created_at' => now()->toIso8601String(),
            'status' => 'planned',
            'summary' => [
                'physical_files' => count($audit['files']),
                'unique_media' => $entries->count(),
                'duplicate_copies' => $entries->sum(fn (array $entry): int => max(0, count($entry['copies']) - 1)),
                'excluded_files' => $excluded->count(),
                'database_references' => count($audit['references']),
            ],
            'roots' => $this->roots,
            'entries' => $entries->all(),
            'excluded' => $excluded->all(),
            'references' => $audit['references'],
        ];
    }

    public function writeManifest(array $plan, ?string $name = null): string
    {
        $directory = storage_path('app/private/media-migrations');
        if (! is_dir($directory) && ! mkdir($directory, 0750, true) && ! is_dir($directory)) {
            throw new RuntimeException("Impossible de créer {$directory}.");
        }
        $name ??= now()->format('Ymd-His').'-'.Str::lower(Str::random(6)).'.json';
        $path = $directory.DIRECTORY_SEPARATOR.basename($name);
        $json = json_encode($plan, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        if (file_put_contents($path, $json."\n", LOCK_EX) === false) {
            throw new RuntimeException("Impossible d’écrire {$path}.");
        }

        return $path;
    }

    public function manifestPath(string $name): string
    {
        return storage_path('app/private/media-migrations').DIRECTORY_SEPARATOR.basename($name);
    }

    public function loadManifest(string $name): array
    {
        $path = $this->manifestPath($name);
        if (! is_file($path)) {
            throw new RuntimeException("Manifeste introuvable : {$path}");
        }

        return json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
    }

    public function apply(string $name): array
    {
        $plan = $this->loadManifest($name);
        if (($plan['status'] ?? null) !== 'planned') {
            throw new RuntimeException('Seul un manifeste au statut planned peut être appliqué.');
        }
        if (($plan['summary']['database_references'] ?? 0) > 0) {
            throw new RuntimeException('Ce manifeste contient des références en base. Leur migration doit être implémentée avant application.');
        }

        $plan['status'] = 'applying';
        $plan['applied_at'] = now()->toIso8601String();
        $this->saveManifest($name, $plan);

        try {
            foreach ($plan['entries'] as $index => $entry) {
                if (filled($entry['media_asset_id'] ?? null)) {
                    continue;
                }

                $source = $this->sourcePath($entry['source'], $plan['roots']);
                if (! is_file($source) || hash_file('sha256', $source) !== $entry['checksum']) {
                    throw new RuntimeException("Source absente ou modifiée : {$entry['source']}");
                }

                $existing = MediaAsset::query()->where('checksum', $entry['checksum'])->first();
                if ($existing) {
                    $plan['entries'][$index]['media_asset_id'] = $existing->id;
                    $plan['entries'][$index]['created_by_manifest'] = false;
                    $this->saveManifest($name, $plan);
                    continue;
                }

                $stream = fopen($source, 'rb');
                if ($stream === false || ! Storage::disk('public')->put($entry['destination'], $stream)) {
                    throw new RuntimeException("Copie impossible vers {$entry['destination']}");
                }
                if (is_resource($stream)) {
                    fclose($stream);
                }

                try {
                    $asset = MediaAsset::query()->create([
                        'type' => $entry['type'],
                        'disk' => 'public',
                        'path' => $entry['destination'],
                        'original_name' => $entry['original_name'],
                        'display_name' => pathinfo($entry['original_name'], PATHINFO_FILENAME),
                        'mime_type' => $entry['mime_type'],
                        'extension' => pathinfo($entry['destination'], PATHINFO_EXTENSION),
                        'size' => $entry['size'],
                        'width' => $entry['width'],
                        'height' => $entry['height'],
                        'checksum' => $entry['checksum'],
                    ]);
                } catch (\Throwable $exception) {
                    Storage::disk('public')->delete($entry['destination']);
                    throw $exception;
                }

                $plan['entries'][$index]['media_asset_id'] = $asset->id;
                $plan['entries'][$index]['created_by_manifest'] = true;
                $this->saveManifest($name, $plan);
            }
        } catch (\Throwable $exception) {
            $plan['status'] = 'failed';
            $plan['error'] = $exception->getMessage();
            $this->saveManifest($name, $plan);
            throw $exception;
        }

        $plan['status'] = 'applied';
        $this->saveManifest($name, $plan);

        return $plan;
    }

    public function rollback(string $name): array
    {
        $plan = $this->loadManifest($name);
        if (! in_array($plan['status'] ?? null, ['applied', 'failed', 'cleaned'], true)) {
            throw new RuntimeException('Ce manifeste ne peut pas être annulé dans son état actuel.');
        }

        if (($plan['status'] ?? null) === 'cleaned') {
            foreach ($plan['entries'] as $entry) {
                $asset = MediaAsset::query()->find($entry['media_asset_id'] ?? null);
                if (! $asset || hash_file('sha256', Storage::disk('public')->path($asset->path)) !== $entry['checksum']) {
                    throw new RuntimeException("Restauration impossible pour {$entry['source']}.");
                }
                foreach ($entry['removed_sources'] ?? [] as $sourceReference) {
                    $target = $this->sourcePath($sourceReference, $plan['roots']);
                    if (! is_dir(dirname($target))) {
                        mkdir(dirname($target), 0755, true);
                    }
                    if (! copy(Storage::disk('public')->path($asset->path), $target)) {
                        throw new RuntimeException("Restauration impossible vers {$sourceReference}.");
                    }
                }
            }
        }

        foreach (array_reverse($plan['entries']) as $entry) {
            if (! ($entry['created_by_manifest'] ?? false) || blank($entry['media_asset_id'] ?? null)) {
                continue;
            }
            $asset = MediaAsset::query()->find($entry['media_asset_id']);
            if (! $asset) {
                continue;
            }
            if (! $asset->canBeDeleted()) {
                throw new RuntimeException("Rollback impossible : le média #{$asset->id} est utilisé.");
            }
            $asset->delete();
        }

        $plan['status'] = 'rolled_back';
        $plan['rolled_back_at'] = now()->toIso8601String();
        $this->saveManifest($name, $plan);

        return $plan;
    }

    public function cleanup(string $name): array
    {
        $plan = $this->loadManifest($name);
        if (($plan['status'] ?? null) !== 'applied') {
            throw new RuntimeException('Seul un manifeste appliqué peut être nettoyé.');
        }

        foreach ($plan['entries'] as $index => $entry) {
            $asset = MediaAsset::query()->find($entry['media_asset_id'] ?? null);
            if (! $asset || ! Storage::disk('public')->exists($asset->path)) {
                throw new RuntimeException("Copie canonique absente pour {$entry['source']}.");
            }
            if (hash_file('sha256', Storage::disk('public')->path($asset->path)) !== $entry['checksum']) {
                throw new RuntimeException("Copie canonique modifiée pour {$entry['source']}.");
            }

            $removed = [];
            foreach ($entry['copies'] as $sourceReference) {
                $source = $this->sourcePath($sourceReference, $plan['roots']);
                if (! is_file($source)) {
                    continue;
                }
                if (hash_file('sha256', $source) !== $entry['checksum']) {
                    throw new RuntimeException("Copie historique modifiée : {$sourceReference}");
                }
                if (! unlink($source)) {
                    throw new RuntimeException("Suppression impossible : {$sourceReference}");
                }
                $removed[] = $sourceReference;
            }
            $plan['entries'][$index]['removed_sources'] = $removed;
            $this->saveManifest($name, $plan);
        }

        foreach ($plan['roots'] as $root) {
            $this->removeEmptyDirectories($root);
        }

        $plan['status'] = 'cleaned';
        $plan['cleaned_at'] = now()->toIso8601String();
        $this->saveManifest($name, $plan);

        return $plan;
    }

    private function saveManifest(string $name, array $plan): void
    {
        $path = $this->manifestPath($name);
        $json = json_encode($plan, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        if (file_put_contents($path, $json."\n", LOCK_EX) === false) {
            throw new RuntimeException("Impossible de mettre à jour {$path}.");
        }
    }

    private function sourcePath(string $source, array $roots): string
    {
        [$scope, $relative] = array_pad(explode(':', $source, 2), 2, null);
        if (! isset($roots[$scope]) || blank($relative) || str_contains($relative, '..')) {
            throw new RuntimeException("Source de manifeste invalide : {$source}");
        }

        return rtrim($roots[$scope], DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$relative;
    }

    private function removeEmptyDirectories(string $root): void
    {
        if (! is_dir($root)) {
            return;
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                @rmdir($item->getPathname());
            }
        }
    }

    private function isEligible(array $file): bool
    {
        return in_array($file['mime_type'], ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'], true);
    }

    private function isAllowedPrivatePath(string $path): bool
    {
        return Str::startsWith($path, ['imports/', 'exports/', 'temporary/', 'livewire-tmp/']);
    }

    private function extensionForMime(string $mime): string
    {
        return match ($mime) {
            'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'application/pdf' => 'pdf',
            default => throw new RuntimeException("Type MIME non pris en charge : {$mime}"),
        };
    }
}
