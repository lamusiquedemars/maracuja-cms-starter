<?php

namespace App\Modules\Media\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Throwable;

class MediaAuditService
{
    /** @var array<string, string> */
    private array $roots;

    /** @param array<string, string>|null $roots */
    public function __construct(?array $roots = null, private readonly ?string $codeRoot = null)
    {
        $this->roots = $roots ?? [
            'public' => public_path('storage'),
            'legacy_public' => storage_path('app/public'),
            'private' => storage_path('app/private'),
        ];
    }

    /**
     * @return array{
     *     roots: array<string, string>,
     *     files: array<int, array<string, mixed>>,
     *     duplicates: array<int, array<string, mixed>>,
     *     references: array<int, array<string, mixed>>,
     *     anomalies: array<int, array<string, mixed>>,
     *     summary: array<string, int>,
     * }
     */
    public function audit(bool $includeDatabase = true): array
    {
        $files = $this->scanFiles();
        $references = $includeDatabase ? $this->scanDatabaseReferences() : [];
        $anomalies = [
            ...$this->filesystemAnomalies($files),
            ...($includeDatabase ? $this->catalogAnomalies($files) : []),
            ...$this->referenceAnomalies($references, $files),
            ...$this->codeAnomalies(),
        ];
        $duplicates = $this->duplicateGroups($files);

        return [
            'roots' => $this->roots,
            'files' => $files,
            'duplicates' => $duplicates,
            'references' => $references,
            'anomalies' => $anomalies,
            'summary' => [
                'files' => count($files),
                'bytes' => array_sum(array_column($files, 'size')),
                'duplicate_groups' => count($duplicates),
                'references' => count($references),
                'anomalies' => count($anomalies),
            ],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function scanFiles(): array
    {
        $files = [];

        foreach ($this->roots as $scope => $root) {
            if (! is_dir($root)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS)
            );

            /** @var SplFileInfo $file */
            foreach ($iterator as $file) {
                if (! $file->isFile() || $file->getFilename() === '.gitignore') {
                    continue;
                }

                $path = $file->getPathname();
                $relativePath = Str::of(substr($path, strlen(rtrim($root, DIRECTORY_SEPARATOR)) + 1))
                    ->replace('\\', '/')
                    ->toString();
                $mimeType = mime_content_type($path) ?: 'application/octet-stream';
                $dimensions = Str::startsWith($mimeType, 'image/') ? @getimagesize($path) : false;

                $files[] = [
                    'scope' => $scope,
                    'path' => $path,
                    'relative_path' => $relativePath,
                    'size' => $file->getSize(),
                    'mime_type' => $mimeType,
                    'sha256' => hash_file('sha256', $path),
                    'width' => is_array($dimensions) ? (int) $dimensions[0] : null,
                    'height' => is_array($dimensions) ? (int) $dimensions[1] : null,
                    'canonical' => $scope === 'public' && $this->isCanonicalPublicPath($relativePath),
                ];
            }
        }

        usort($files, fn (array $left, array $right): int => [$left['scope'], $left['relative_path']] <=> [$right['scope'], $right['relative_path']]);

        return $files;
    }

    /** @param array<int, array<string, mixed>> $files
     * @return array<int, array<string, mixed>>
     */
    private function duplicateGroups(array $files): array
    {
        return collect($files)
            ->groupBy('sha256')
            ->filter(fn ($group): bool => $group->count() > 1)
            ->map(fn ($group, string $sha256): array => [
                'sha256' => $sha256,
                'size' => (int) $group->first()['size'],
                'paths' => $group->map(fn (array $file): string => $file['scope'].':'.$file['relative_path'])->values()->all(),
            ])
            ->values()
            ->all();
    }

    /** @param array<int, array<string, mixed>> $files
     * @return array<int, array<string, mixed>>
     */
    private function filesystemAnomalies(array $files): array
    {
        $anomalies = [];

        foreach ($files as $file) {
            $scope = (string) $file['scope'];
            $relativePath = (string) $file['relative_path'];

            if ($scope === 'public' && ! $file['canonical']) {
                $anomalies[] = $this->anomaly('public_path_forbidden', $scope.':'.$relativePath, 'Média public hors de media/images ou media/documents.');
            }

            if ($scope === 'legacy_public') {
                $anomalies[] = $this->anomaly('legacy_public_file', $scope.':'.$relativePath, 'Fichier présent dans storage/app/public.');
            }

            if ($scope === 'private' && $this->looksLikePublicMedia($file) && ! $this->isAllowedPrivatePath($relativePath)) {
                $anomalies[] = $this->anomaly('public_media_in_private', $scope.':'.$relativePath, 'Image ou document public probable dans le stockage privé.');
            }
        }

        return $anomalies;
    }

    /** @param array<int, array<string, mixed>> $files
     * @return array<int, array<string, mixed>>
     */
    private function catalogAnomalies(array $files): array
    {
        try {
            if (! Schema::hasTable('media_assets')) {
                return [];
            }

            $assets = DB::table('media_assets')->get(['id', 'disk', 'path']);
        } catch (Throwable) {
            return [];
        }

        $canonicalFiles = collect($files)
            ->where('scope', 'public')
            ->where('canonical', true)
            ->pluck('relative_path')
            ->flip();
        $cataloguedPaths = $assets
            ->where('disk', 'public')
            ->pluck('path')
            ->flip();
        $anomalies = [];

        foreach ($canonicalFiles->keys() as $path) {
            if (! $cataloguedPaths->has($path)) {
                $anomalies[] = $this->anomaly('file_without_catalog', 'public:'.$path, 'Fichier canonique sans entrée MediaAsset.');
            }
        }

        foreach ($assets as $asset) {
            if ($asset->disk !== 'public') {
                $anomalies[] = $this->anomaly('invalid_media_disk', 'media_assets#'.$asset->id, (string) $asset->disk);

                continue;
            }

            if (! $this->isCanonicalPublicPath($asset->path)) {
                $anomalies[] = $this->anomaly('non_canonical_catalog_path', 'media_assets#'.$asset->id, $asset->path);
            }

            if (! $canonicalFiles->has($asset->path)) {
                $anomalies[] = $this->anomaly('catalog_without_file', 'media_assets#'.$asset->id, $asset->path);
            }
        }

        return $anomalies;
    }

    /** @return array<int, array<string, mixed>> */
    private function scanDatabaseReferences(): array
    {
        $references = [];
        $tables = ['pages', 'news_posts', 'articles', 'events', 'gallery_images', 'site_settings', 'segment_messages'];

        try {
            DB::connection()->getPdo();
        } catch (Throwable $exception) {
            return [[
                'source' => 'database',
                'value' => null,
                'normalized_path' => null,
                'error' => $exception->getMessage(),
            ]];
        }

        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $columns = Schema::getColumnListing($table);

            DB::table($table)
                ->orderBy('id')
                ->chunkById(100, function ($rows) use ($table, $columns, &$references): void {
                    foreach ($rows as $row) {
                        foreach ($columns as $column) {
                            $value = $row->{$column} ?? null;

                            if (! is_string($value) || $value === '') {
                                continue;
                            }

                            foreach ($this->extractMediaPaths($value) as $path) {
                                $references[] = [
                                    'source' => $table.'#'.($row->id ?? '?').'.'.$column,
                                    'value' => $path,
                                    'normalized_path' => $this->normalizeReferencePath($path),
                                    'error' => null,
                                ];
                            }
                        }
                    }
                });
        }

        return $references;
    }

    /** @return array<int, string> */
    private function extractMediaPaths(string $value): array
    {
        preg_match_all(
            '~(?:https?://[^\s"\'<>]+)?/storage/[^\s"\'<>]+|(?<![A-Za-z0-9_/.-])(?:media|pages|news|articles|events|galleries|site|settings|uploads|attachments)/[^\s"\'<>]+~i',
            $value,
            $matches
        );

        return array_values(array_unique(array_map(
            fn (string $path): string => html_entity_decode(rtrim($path, '.,);]}'), ENT_QUOTES | ENT_HTML5),
            $matches[0] ?? []
        )));
    }

    private function normalizeReferencePath(string $reference): ?string
    {
        $path = parse_url($reference, PHP_URL_PATH);

        if (! is_string($path)) {
            return null;
        }

        return Str::startsWith($path, '/storage/') ? Str::after($path, '/storage/') : ltrim($path, '/');
    }

    /** @param array<int, array<string, mixed>> $references
     * @param  array<int, array<string, mixed>>  $files
     * @return array<int, array<string, mixed>>
     */
    private function referenceAnomalies(array $references, array $files): array
    {
        $publicPaths = collect($files)
            ->where('scope', 'public')
            ->pluck('relative_path')
            ->flip();
        $anomalies = [];

        foreach ($references as $reference) {
            if ($reference['error'] ?? null) {
                $anomalies[] = $this->anomaly('database_unavailable', 'database', (string) $reference['error']);

                continue;
            }

            $value = (string) $reference['value'];
            $normalizedPath = $reference['normalized_path'];

            if (Str::startsWith($value, ['http://', 'https://'])) {
                $anomalies[] = $this->anomaly('domain_url_in_database', (string) $reference['source'], $value);
            }

            if (! is_string($normalizedPath) || ! $publicPaths->has($normalizedPath)) {
                $anomalies[] = $this->anomaly('missing_public_file', (string) $reference['source'], $value);
            } elseif (! $this->isCanonicalPublicPath($normalizedPath)) {
                $anomalies[] = $this->anomaly('legacy_reference', (string) $reference['source'], $value);
            }
        }

        return $anomalies;
    }

    /** @return array<int, array<string, mixed>> */
    private function codeAnomalies(): array
    {
        $root = $this->codeRoot ?? app_path();

        if (! is_dir($root)) {
            return [];
        }

        $anomalies = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS));

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $contents = file_get_contents($file->getPathname());

            if (! is_string($contents)) {
                continue;
            }

            preg_match_all(
                "~->directory\((?!['\"](?:media/(?:images|documents)|imports|exports|temporary|livewire-tmp)(?:/|['\"]))[^\r\n]+~",
                $contents,
                $matches,
            );

            foreach ($matches[0] ?? [] as $match) {
                $anomalies[] = $this->anomaly(
                    'forbidden_upload_directory',
                    Str::after($file->getPathname(), rtrim(base_path(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR),
                    trim($match)
                );
            }

            if (preg_match('/(?<![A-Za-z0-9_\\\\])RichEditor::make\(/', $contents) === 1) {
                $anomalies[] = $this->anomaly(
                    'rich_editor_native_attachments',
                    Str::after($file->getPathname(), rtrim(base_path(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR),
                    'RichEditor sans intégration explicite au Media Picker Maracuja.'
                );
            }
        }

        return $anomalies;
    }

    private function isCanonicalPublicPath(string $path): bool
    {
        return Str::startsWith($path, ['media/images/', 'media/documents/']);
    }

    /** @param array<string, mixed> $file */
    private function looksLikePublicMedia(array $file): bool
    {
        return Str::startsWith((string) $file['mime_type'], 'image/') || $file['mime_type'] === 'application/pdf';
    }

    private function isAllowedPrivatePath(string $path): bool
    {
        return Str::startsWith($path, ['imports/', 'exports/', 'temporary/', 'livewire-tmp/']);
    }

    /** @return array{type: string, location: string, detail: string} */
    private function anomaly(string $type, string $location, string $detail): array
    {
        return compact('type', 'location', 'detail');
    }
}
