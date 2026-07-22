<?php

namespace App\Modules\Media\Concerns;

use App\Modules\Media\Models\MediaUsage;
use App\Modules\Media\Models\MediaAsset;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait TracksMediaUsages
{
    protected static function bootTracksMediaUsages(): void
    {
        static::saved(fn (self $model) => $model->syncMediaUsages());
        static::deleted(fn (self $model) => $model->mediaUsages()->delete());
    }

    public function mediaUsages(): MorphMany
    {
        return $this->morphMany(MediaUsage::class, 'usable');
    }

    public function syncMediaUsages(): void
    {
        $references = collect($this->mediaUsageReferences())
            ->filter(fn (array $reference): bool => filled($reference['media_asset_id'] ?? null))
            ->map(fn (array $reference): array => [
                'media_asset_id' => $reference['media_asset_id'],
                'field' => $reference['field'],
                'context' => $reference['context'] ?? '',
            ])
            ->unique(fn (array $reference): string => implode(':', [
                $reference['media_asset_id'],
                $reference['field'],
                $reference['context'],
            ]))
            ->values();

        $this->mediaUsages()->delete();

        if ($references->isNotEmpty()) {
            $this->mediaUsages()->createMany($references->all());
        }
    }

    protected function trackedMedia(string $relation, int|string|null $mediaId): ?MediaAsset
    {
        if (blank($mediaId)) {
            return null;
        }

        if ($this->relationLoaded($relation) && (string) $this->getRelation($relation)?->getKey() !== (string) $mediaId) {
            $this->unsetRelation($relation);
        }

        return $this->getRelationValue($relation);
    }

    /** @return array<int, array{media_asset_id: int, field: string, context: string}> */
    protected function mediaUsageReferencesFromHtml(?string $html, string $field, string $context = ''): array
    {
        if (blank($html)) {
            return [];
        }

        preg_match_all('/\bid=["\']media-(\d+)["\']/i', $html, $matches);

        preg_match_all('/\b(?:href|src)=["\'][^"\']*\/storage\/(media\/(?:images|documents)\/[^"\'?#]+)[^"\']*["\']/i', $html, $pathMatches);

        $pathIds = MediaAsset::query()
            ->whereIn('path', array_map('rawurldecode', $pathMatches[1] ?? []))
            ->pluck('id');

        return collect($matches[1] ?? [])
            ->merge($pathIds)
            ->unique()
            ->map(fn (string $id): array => [
                'media_asset_id' => (int) $id,
                'field' => $field,
                'context' => $context,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{media_asset_id: int|string|null, field: string, context?: string|null}>
     */
    abstract protected function mediaUsageReferences(): array;
}
