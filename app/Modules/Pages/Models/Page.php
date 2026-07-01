<?php

namespace App\Modules\Pages\Models;

use App\Support\Modules;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
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
