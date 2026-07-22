<?php

namespace App\Modules\SiteSettings\Models;

use App\Modules\Media\Concerns\TracksMediaUsages;
use App\Modules\Media\Models\MediaAsset;
use App\Support\MediaFiles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteSetting extends Model
{
    use TracksMediaUsages;

    protected $fillable = [
        'site_name',
        'baseline',
        'default_seo_title',
        'default_seo_description',
        'contact_email',
        'phone',
        'address',
        'logo_path',
        'logo_media_id',
        'favicon_path',
        'favicon_media_id',
        'default_og_image_path',
        'default_og_media_id',
        'social_links',
        'contact_form_show_name',
        'contact_form_show_phone',
        'contact_form_show_subject',
        'contact_form_send_admin_email',
        'contact_form_send_confirmation_email',
    ];

    protected function casts(): array
    {
        return [
            'social_links' => 'array',
            'contact_form_show_name' => 'boolean',
            'contact_form_show_phone' => 'boolean',
            'contact_form_show_subject' => 'boolean',
            'contact_form_send_admin_email' => 'boolean',
            'contact_form_send_confirmation_email' => 'boolean',
        ];
    }

    public function logoMedia(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'logo_media_id');
    }

    public function faviconMedia(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'favicon_media_id');
    }

    public function defaultOgMedia(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'default_og_media_id');
    }

    public function logoUrl(): ?string
    {
        return $this->trackedMedia('logoMedia', $this->logo_media_id)?->url() ?? MediaFiles::url($this->logo_path);
    }

    public function faviconUrl(): ?string
    {
        return $this->trackedMedia('faviconMedia', $this->favicon_media_id)?->url() ?? MediaFiles::url($this->favicon_path);
    }

    public function defaultOgImageUrl(): ?string
    {
        return $this->trackedMedia('defaultOgMedia', $this->default_og_media_id)?->url() ?? MediaFiles::url($this->default_og_image_path);
    }

    protected function mediaUsageReferences(): array
    {
        return [
            ['media_asset_id' => $this->logo_media_id, 'field' => 'logo_media_id'],
            ['media_asset_id' => $this->favicon_media_id, 'field' => 'favicon_media_id'],
            ['media_asset_id' => $this->default_og_media_id, 'field' => 'default_og_media_id'],
        ];
    }

    public function getContactFormShowNameAttribute(?bool $value): bool
    {
        return $value ?? true;
    }

    public function getContactFormShowPhoneAttribute(?bool $value): bool
    {
        return $value ?? true;
    }

    public function getContactFormShowSubjectAttribute(?bool $value): bool
    {
        return $value ?? true;
    }

    public function getContactFormSendAdminEmailAttribute(?bool $value): bool
    {
        return $value ?? true;
    }

    public function getContactFormSendConfirmationEmailAttribute(?bool $value): bool
    {
        return $value ?? false;
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'site_name' => 'Maracuja CMS',
            'baseline' => 'Site vitrine administrable, sobre et sur mesure.',
            'default_seo_title' => 'Maracuja CMS',
            'default_seo_description' => 'Un starter Laravel + Filament pour sites vitrines administrables.',
            'default_og_image_path' => null,
            'contact_email' => 'contact@example.test',
            'contact_form_show_name' => true,
            'contact_form_show_phone' => true,
            'contact_form_show_subject' => true,
            'contact_form_send_admin_email' => true,
            'contact_form_send_confirmation_email' => false,
        ]);
    }
}
