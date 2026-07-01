<?php

namespace App\Modules\Events\Models;

use App\Modules\Venues\Models\Venue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Event extends Model
{
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_POSTPONED = 'postponed';
    public const STATUS_SOLD_OUT = 'sold_out';

    protected $fillable = [
        'venue_id',
        'title',
        'slug',
        'type',
        'status',
        'starts_at',
        'ends_at',
        'timezone',
        'excerpt',
        'description',
        'image_path',
        'ticket_url',
        'external_url',
        'seo_title',
        'seo_description',
        'is_featured',
        'is_published',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_featured' => 'boolean',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
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

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query
            ->visible()
            ->where('starts_at', '>=', now()->startOfDay())
            ->orderBy('starts_at');
    }

    public function scopePast(Builder $query): Builder
    {
        return $query
            ->visible()
            ->where('starts_at', '<', now()->startOfDay())
            ->orderByDesc('starts_at');
    }

    public function publicExcerpt(): string
    {
        if (filled($this->excerpt)) {
            return html_entity_decode($this->excerpt, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        $excerpt = str($this->description ?? '')
            ->stripTags()
            ->squish()
            ->limit(180)
            ->toString();

        return html_entity_decode($excerpt, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_CANCELLED => 'Annulé',
            self::STATUS_POSTPONED => 'Reporté',
            self::STATUS_SOLD_OUT => 'Complet',
            default => 'Programmé',
        };
    }

    public function venueLabel(): ?string
    {
        if (! $this->venue) {
            return null;
        }

        return collect([$this->venue->name, $this->venue->publicLocation()])
            ->map(fn (?string $part): string => trim((string) $part))
            ->filter()
            ->join(' - ');
    }

    protected static function booted(): void
    {
        static::saving(function (Event $event): void {
            if (! $event->slug) {
                $event->slug = static::uniqueSlugForTitle($event->title);
            }
        });
    }

    private static function uniqueSlugForTitle(string $title): string
    {
        $base = Str::slug($title) ?: 'evenement';
        $slug = $base;
        $suffix = 2;

        while (static::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
