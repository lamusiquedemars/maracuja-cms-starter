<?php

declare(strict_types=1);

namespace App\Support;

use Maracuja\SiteCore\Foundation\Contracts\SitePresentation;

final class ReferenceSitePresentation implements SitePresentation
{
    public function theme(): string
    {
        return SiteManifest::presentation()['theme'];
    }

    public function galleryPreset(): ?string
    {
        return SiteManifest::presentation()['gallery_preset'];
    }

    public function galleryPresetLabel(): ?string
    {
        return SiteManifest::galleryPresetLabel($this->galleryPreset());
    }
}
