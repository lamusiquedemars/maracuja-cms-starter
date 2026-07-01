<?php

namespace Tests\Unit;

use App\Modules\Gallery\Models\GalleryImage;
use App\Support\MediaFiles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaFilesTest extends TestCase
{
    use RefreshDatabase;

    public function test_options_list_images_from_context_directory_with_dimensions(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('galleries/home/tiny.png', base64_decode($this->tinyPng()));
        Storage::disk('public')->put('news/tiny.png', base64_decode($this->tinyPng()));
        Storage::disk('public')->put('galleries/home/readme.txt', 'ignore me');

        $options = MediaFiles::options('galleries/home');

        $this->assertArrayHasKey('galleries/home/tiny.png', $options);
        $this->assertStringContainsString('1 x 1', $options['galleries/home/tiny.png']);
        $this->assertArrayNotHasKey('news/tiny.png', $options);
        $this->assertArrayNotHasKey('galleries/home/readme.txt', $options);
    }

    public function test_gallery_dimensions_are_filled_when_image_is_saved(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('galleries/home/tiny.png', base64_decode($this->tinyPng()));

        $image = GalleryImage::query()->create([
            'title' => 'Tiny',
            'image_path' => 'galleries/home/tiny.png',
            'is_published' => true,
        ]);

        $this->assertSame(1, $image->width);
        $this->assertSame(1, $image->height);
    }

    private function tinyPng(): string
    {
        return 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=';
    }
}
