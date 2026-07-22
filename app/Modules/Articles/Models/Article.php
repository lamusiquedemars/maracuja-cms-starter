<?php

namespace App\Modules\Articles\Models;

use App\Modules\Media\Concerns\TracksMediaUsages;
use App\Modules\Media\Models\MediaAsset;
use App\Support\MediaFiles;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Article extends Model
{
    use TracksMediaUsages;

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'image_path',
        'image_media_id',
        'body_blocks',
        'seo_title',
        'seo_description',
        'is_published',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'body_blocks' => 'array',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function imageMedia(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'image_media_id');
    }

    public function imageUrl(): ?string
    {
        return $this->trackedMedia('imageMedia', $this->image_media_id)?->url() ?? MediaFiles::url($this->image_path);
    }

    protected function mediaUsageReferences(): array
    {
        $references = [['media_asset_id' => $this->image_media_id, 'field' => 'image_media_id']];

        foreach ($this->body_blocks ?? [] as $index => $block) {
            if (($block['type'] ?? null) === 'image' && filled($block['media_id'] ?? null)) {
                $references[] = [
                    'media_asset_id' => $block['media_id'],
                    'field' => 'body_blocks',
                    'context' => 'block:'.$index,
                ];
            }

            foreach (['text', 'note'] as $htmlField) {
                $references = [
                    ...$references,
                    ...$this->mediaUsageReferencesFromHtml(
                        $block[$htmlField] ?? null,
                        'body_blocks',
                        'block:'.$index.':'.$htmlField,
                    ),
                ];
            }
        }

        return $references;
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query
            ->where('is_published', true)
            ->where(function (Builder $query): void {
                $query->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    public function scopeForListing(Builder $query): Builder
    {
        return $query
            ->visible()
            ->orderByDesc('published_at')
            ->orderByDesc('created_at');
    }

    public function publicExcerpt(): string
    {
        if (filled($this->excerpt)) {
            return $this->excerpt;
        }

        $firstText = collect($this->body_blocks ?? [])
            ->first(fn (array $block): bool => ($block['type'] ?? null) === 'rich_text' && filled($block['text'] ?? null));

        return str($firstText['text'] ?? '')
            ->stripTags()
            ->squish()
            ->limit(180)
            ->toString();
    }
}
