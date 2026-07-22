<?php

namespace Tests\Unit;

use App\Modules\Media\Enums\MediaType;
use App\Modules\Media\Filament\Forms\Components\MediaIdSelect;
use App\Modules\Media\Models\MediaAsset;
use Tests\TestCase;

class MediaIdSelectTest extends TestCase
{
    public function test_it_renders_an_image_preview_with_useful_details(): void
    {
        $media = new MediaAsset([
            'type' => MediaType::Image,
            'path' => 'media/images/2026/07/photo.webp',
            'display_name' => 'Portrait <été>',
            'original_name' => 'IMG_1234.webp',
            'size' => 1536,
            'width' => 800,
            'height' => 600,
        ]);

        $label = MediaIdSelect::make('media_id')->optionLabel($media);

        $this->assertStringContainsString('src="/storage/media/images/2026/07/photo.webp"', $label);
        $this->assertStringContainsString('Portrait &lt;été&gt;', $label);
        $this->assertStringNotContainsString('Portrait <été>', $label);
        $this->assertStringContainsString('IMG_1234.webp · 800 × 600 px · 1,5 Ko', $label);
    }

    public function test_it_renders_a_pdf_badge_for_a_document(): void
    {
        $media = new MediaAsset([
            'type' => MediaType::Document,
            'path' => 'media/documents/2026/07/tarifs.pdf',
            'display_name' => 'Grille tarifaire',
            'original_name' => 'tarifs-2026.pdf',
            'size' => 2048,
        ]);

        $label = MediaIdSelect::make('media_id')->optionLabel($media);

        $this->assertStringContainsString('>PDF</span>', $label);
        $this->assertStringContainsString('Grille tarifaire', $label);
        $this->assertStringContainsString('tarifs-2026.pdf · 2,0 Ko', $label);
        $this->assertStringNotContainsString('<img', $label);
    }
}
