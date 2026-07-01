<?php

namespace App\Modules\Gallery\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Gallery extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'intro',
        'position',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    public function images(): HasMany
    {
        return $this->hasMany(GalleryImage::class)->orderBy('position');
    }

    public function isSystemGallery(): bool
    {
        return in_array($this->slug, [
            'home',
            config('maracuja.gallery.slug', 'home'),
        ], true);
    }

    protected static function booted(): void
    {
        static::saving(function (Gallery $gallery): void {
            if (! $gallery->slug) {
                $gallery->slug = static::uniqueSlugForTitle($gallery->title);
            }
        });
    }

    private static function uniqueSlugForTitle(string $title): string
    {
        $base = Str::slug($title) ?: 'galerie';
        $slug = $base;
        $suffix = 2;

        while (static::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
