<?php

namespace App\Modules\Gallery\Models;

use App\Support\MediaFiles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GalleryImage extends Model
{
    protected $fillable = [
        'title',
        'gallery_id',
        'caption',
        'credit',
        'image_path',
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

    protected static function booted(): void
    {
        static::saving(function (GalleryImage $image): void {
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
        return $this->alt_text ?: $this->title;
    }

    public function getResolvedImageUrlAttribute(): ?string
    {
        return MediaFiles::url($this->image_path);
    }
}
