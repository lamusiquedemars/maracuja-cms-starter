<?php

namespace App\Modules\Venues\Models;

use App\Modules\Events\Models\Event;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Venue extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'type',
        'address',
        'postal_code',
        'city',
        'region',
        'country',
        'maps_url',
        'website_url',
        'public_email',
        'public_phone',
        'contact_name',
        'contact_email',
        'contact_phone',
        'capacity',
        'accessibility_notes',
        'technical_notes',
        'private_notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function publicLocation(): string
    {
        return collect([$this->city, $this->region, $this->country])
            ->map(fn (?string $part): string => trim((string) $part))
            ->filter()
            ->join(', ');
    }

    public function fullAddress(): string
    {
        return collect([$this->address, trim($this->postal_code.' '.$this->city), $this->country])
            ->map(fn (?string $part): string => trim((string) $part))
            ->filter(fn (string $part): bool => filled($part))
            ->join(', ');
    }

    protected static function booted(): void
    {
        static::saving(function (Venue $venue): void {
            if (! $venue->slug) {
                $venue->slug = static::uniqueSlugForName($venue->name);
            }
        });
    }

    private static function uniqueSlugForName(string $name): string
    {
        $base = Str::slug($name) ?: 'lieu';
        $slug = $base;
        $suffix = 2;

        while (static::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
