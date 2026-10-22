<?php

namespace Tests\Unit;

use App\Support\ReferenceSitePresentation;
use App\Support\SiteManifest;
use Tests\TestCase;

class SiteManifestPresentationTest extends TestCase
{
    public function test_it_exposes_the_released_gallery_preset_from_the_manifest(): void
    {
        config(['maracuja.module_manifest.enabled' => true]);

        $presentation = app(ReferenceSitePresentation::class);

        $this->assertSame('default', $presentation->theme());
        $this->assertSame('grid', $presentation->galleryPreset());
        $this->assertSame('Grille', $presentation->galleryPresetLabel());
    }

    public function test_it_uses_local_configuration_when_the_manifest_is_disabled_for_tests(): void
    {
        config([
            'maracuja.module_manifest.enabled' => false,
            'maracuja.gallery.layout' => 'featured',
        ]);

        $this->assertSame('featured', SiteManifest::presentation()['gallery_preset']);
        $this->assertSame('Mise en avant', SiteManifest::galleryPresetLabel('featured'));
    }
}
