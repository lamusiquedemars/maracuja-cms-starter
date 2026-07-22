<?php

namespace App\Modules\Gallery\Models;

use App\Modules\Media\Concerns\TracksMediaUsages;
use App\Modules\Media\Models\MediaAsset;
use App\Support\MediaFiles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GalleryImage extends Model
{
    use TracksMediaUsages;

    protected $fillable = [
        'title',
        'gallery_id',
        'caption',
        'credit',
        'image_path',
        'media_asset_id',
        'alt_text',
        'width',
        'height',
        'position',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'width' => 'integer',
            'height' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    public function gallery(): BelongsTo
    {
        return $this->belongsTo(Gallery::class);
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'media_asset_id');
    }

    protected static function booted(): void
    {
        static::saving(function (GalleryImage $image): void {
            if ($image->media) {
                $image->width = $image->media->width;
                $image->height = $image->media->height;

                return;
            }

            if (! $image->image_path) {
                return;
            }

            $dimensions = MediaFiles::dimensions($image->image_path);

            if (! $dimensions) {
                return;
            }

            $image->width = $dimensions['width'];
            $image->height = $dimensions['height'];
        });
    }

    public function getAltAttribute(): string
    {
        return $this->alt_text ?: $this->trackedMedia('media', $this->media_asset_id)?->alt_text ?: $this->title;
    }

    public function getResolvedImageUrlAttribute(): ?string
    {
        return $this->trackedMedia('media', $this->media_asset_id)?->url() ?? MediaFiles::url($this->image_path);
    }

    protected function mediaUsageReferences(): array
    {
        return [['media_asset_id' => $this->media_asset_id, 'field' => 'media_asset_id']];
    }
}
