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
        'path',
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

    public function isImage(): bool
    {
        return $this->type === MediaType::Image;
    }

    public function isDocument(): bool
    {
        return $this->type === MediaType::Document;
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
