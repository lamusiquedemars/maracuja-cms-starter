<?php

namespace Tests\Feature;

use App\Modules\Gallery\Models\Gallery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GalleryTest extends TestCase
{
    use RefreshDatabase;

    public function test_gallery_slug_is_generated_from_title(): void
    {
        $gallery = Gallery::query()->create([
            'title' => 'Galerie Atelier',
        ]);

        $this->assertSame('galerie-atelier', $gallery->slug);
    }

    public function test_system_gallery_can_be_identified(): void
    {
        $gallery = Gallery::query()->updateOrCreate(['slug' => 'home'], [
            'title' => 'Accueil',
        ]);

        $this->assertTrue($gallery->isSystemGallery());
    }
}
