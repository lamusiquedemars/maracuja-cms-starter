<?php

namespace Tests\Feature\Media;

use App\Modules\Media\Services\MediaStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageVariantServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('local');
    }

    public function test_it_deletes_the_private_source_and_all_web_variants_with_the_media(): void
    {
        $media = app(MediaStorageService::class)
            ->store(UploadedFile::fake()->image('atelier.jpg', 2000, 1200));

        $variants = collect($media->variants)->pluck('path')->all();
        $source = $media->source_path;

        Storage::disk('public')->assertExists($variants);
        Storage::disk('local')->assertExists((string) $source);

        $media->delete();

        Storage::disk('public')->assertMissing($variants);
        Storage::disk('local')->assertMissing((string) $source);
        $this->assertDatabaseCount('media_assets', 0);
    }
}
