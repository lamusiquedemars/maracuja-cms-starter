<?php

namespace App\Modules\Media\Models;

use App\Models\User;
use App\Modules\Media\Enums\MediaType;
use App\Modules\Media\Exceptions\MediaAssetInUseException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class MediaAsset extends Model
{
    protected $fillable = [
        'type',
        'disk',
        'source_disk',
        'source_path',
        'path',
        'thumbnail_path',
        'variants',
        'original_name',
        'display_name',
        'mime_type',
        'extension',
        'size',
        'width',
        'height',
        'alt_text',
        'caption',
        'credit',
        'checksum',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => MediaType::class,
            'size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'variants' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (MediaAsset $media): void {
            if ($media->usages()->exists()) {
                throw MediaAssetInUseException::forMedia($media->display_name);
            }
        });

        static::deleted(function (MediaAsset $media): void {
            Storage::disk($media->disk)->delete($media->path);

            Storage::disk($media->disk)->delete(collect($media->variants ?? [])
                ->pluck('path')
                ->reject(fn (string $path): bool => $path === $media->path)
                ->all());

            if (filled($media->source_path)) {
                Storage::disk($media->source_disk ?: 'local')->delete($media->source_path);
            }

            if (filled($media->thumbnail_path)) {
                Storage::disk($media->disk)->delete($media->thumbnail_path);
            }
        });
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function usages(): HasMany
    {
        return $this->hasMany(MediaUsage::class);
    }

    public function scopeImages(Builder $query): Builder
    {
        return $query->where('type', MediaType::Image);
    }

    public function scopeDocuments(Builder $query): Builder
    {
        return $query->where('type', MediaType::Document);
    }

    public function scopeVideos(Builder $query): Builder
    {
        return $query->where('type', MediaType::Video);
    }

    public function isImage(): bool
    {
        return $this->type === MediaType::Image;
    }

    public function isDocument(): bool
    {
        return $this->type === MediaType::Document;
    }

    public function isVideo(): bool
    {
        return $this->type === MediaType::Video;
    }

    public function canBeDeleted(): bool
    {
        return ! $this->usages()->exists();
    }

    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    public function publicPath(): string
    {
        return '/storage/'.ltrim($this->path, '/');
    }

    public function thumbnailUrl(): ?string
    {
        return filled($this->thumbnail_path)
            ? Storage::disk($this->disk)->url($this->thumbnail_path)
            : null;
    }

    public function srcset(): ?string
    {
        if (! $this->isImage() || empty($this->variants)) {
            return null;
        }

        return collect($this->variants)
            ->filter(fn (array $variant): bool => filled($variant['path'] ?? null) && filled($variant['width'] ?? null))
            ->map(fn (array $variant): string => Storage::disk($this->disk)->url($variant['path']).' '.$variant['width'].'w')
            ->implode(', ');
    }

    public function formattedSize(): string
    {
        if ($this->size < 1024) {
            return $this->size.' o';
        }

        if ($this->size < 1024 * 1024) {
            return number_format($this->size / 1024, 1, ',', ' ').' Ko';
        }

        return number_format($this->size / (1024 * 1024), 1, ',', ' ').' Mo';
    }

    public function dimensionsLabel(): ?string
    {
        return $this->width && $this->height ? "{$this->width} × {$this->height} px" : null;
    }
}
