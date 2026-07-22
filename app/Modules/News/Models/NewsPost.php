<?php

namespace App\Modules\News\Models;

use App\Modules\Media\Concerns\TracksMediaUsages;
use App\Modules\Media\Models\MediaAsset;
use App\Support\MediaFiles;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsPost extends Model
{
    use TracksMediaUsages;

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'image_path',
        'image_media_id',
        'seo_title',
        'seo_description',
        'is_published',
        'is_pinned',
        'has_detail_page',
        'published_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'is_pinned' => 'boolean',
            'has_detail_page' => 'boolean',
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
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
        return [
            ['media_asset_id' => $this->image_media_id, 'field' => 'image_media_id'],
            ...$this->mediaUsageReferencesFromHtml($this->content, 'content'),
        ];
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query
            ->where('is_published', true)
            ->where(function (Builder $query): void {
                $query->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            })
            ->where(function (Builder $query): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            });
    }

    public function scopeForListing(Builder $query): Builder
    {
        return $query
            ->visible()
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->orderByDesc('created_at');
    }

    public function hasDetailPage(): bool
    {
        return $this->has_detail_page && filled($this->content);
    }
}
