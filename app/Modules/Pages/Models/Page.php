<?php

namespace App\Modules\Pages\Models;

use App\Modules\Media\Concerns\TracksMediaUsages;
use App\Modules\Media\Models\MediaAsset;
use App\Support\MediaFiles;
use App\Support\Modules;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Page extends Model
{
    use TracksMediaUsages;

    public const TYPE_SYSTEM = 'system';

    public const TYPE_TEXT = 'text';

    public const TYPE_MODULE = 'module';

    protected $fillable = [
        'title',
        'slug',
        'template',
        'type',
        'excerpt',
        'hero_title',
        'hero_subtitle',
        'hero_image_path',
        'hero_media_id',
        'content',
        'seo_title',
        'seo_description',
        'is_published',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function heroMedia(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'hero_media_id');
    }

    public function heroImageUrl(): ?string
    {
        return $this->trackedMedia('heroMedia', $this->hero_media_id)?->url() ?? MediaFiles::url($this->hero_image_path);
    }

    protected function mediaUsageReferences(): array
    {
        return [
            ['media_asset_id' => $this->hero_media_id, 'field' => 'hero_media_id'],
            ...$this->mediaUsageReferencesFromHtml($this->content, 'content'),
        ];
    }

    public function isSystem(): bool
    {
        return $this->type === self::TYPE_SYSTEM;
    }

    public function isText(): bool
    {
        return $this->type === self::TYPE_TEXT;
    }

    public function isModule(): bool
    {
        return $this->type === self::TYPE_MODULE;
    }

    public function publicUrl(): ?string
    {
        return match ($this->slug) {
            'accueil' => route('home'),
            'actualites' => route('news.index'),
            'contact' => Modules::enabled('contact_form') ? route('contact') : null,
            default => route('pages.show', $this->slug),
        };
    }
}
