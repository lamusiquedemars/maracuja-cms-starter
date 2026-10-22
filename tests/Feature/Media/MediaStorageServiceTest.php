<?php

namespace Tests\Feature\Media;

use App\Models\User;
use Maracuja\SiteCore\Media\Enums\MediaType;
use Maracuja\SiteCore\Media\Models\MediaAsset;
use Maracuja\SiteCore\Media\Services\MediaStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MediaStorageServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('local');
        Carbon::setTestNow('2026-07-21 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_keeps_an_original_private_and_creates_web_variants_for_an_image(): void
    {
        $uploader = User::factory()->create();
        $media = app(MediaStorageService::class)->store(UploadedFile::fake()->image('atelier.jpg', 1200, 800), [
            'display_name' => 'Portrait de l’atelier',
            'alt_text' => 'Portrait dans l’atelier',
        ], $uploader);

        $this->assertSame(MediaType::Image, $media->type);
        $this->assertStringStartsWith('media/images/2026/07/', $media->path);
        $this->assertStringEndsWith('.webp', $media->path);
        $this->assertSame('webp', $media->extension);
        $this->assertSame('Portrait de l’atelier', $media->display_name);
        $this->assertSame($uploader->id, $media->uploaded_by);
        $this->assertCount(3, $media->variants);
        $this->assertStringContainsString('640w', (string) $media->srcset());
        $this->assertStringContainsString('1200w', (string) $media->srcset());
        Storage::disk('public')->assertExists($media->path);
        Storage::disk('local')->assertExists((string) $media->source_path);
    }

    public function test_it_stores_a_pdf_in_the_canonical_documents_directory(): void
    {
        $file = UploadedFile::fake()->createWithContent('Catalogue été.pdf', "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF");

        $media = app(MediaStorageService::class)->store($file);

        $this->assertSame(MediaType::Document, $media->type);
        $this->assertMatchesRegularExpression('~^media/documents/2026/07/[0-9A-HJKMNP-TV-Z]{26}\.pdf$~', $media->path);
        $this->assertSame('application/pdf', $media->mime_type);
        $this->assertNull($media->width);
        $this->assertNull($media->height);
        Storage::disk('public')->assertExists($media->path);
    }

    public function test_it_stores_a_video_in_the_canonical_videos_directory(): void
    {
        $file = UploadedFile::fake()->create('presentation.mp4', 128, 'video/mp4');

        $media = app(MediaStorageService::class)->store($file);

        $this->assertSame(MediaType::Video, $media->type);
        $this->assertMatchesRegularExpression('~^media/videos/2026/07/[0-9A-HJKMNP-TV-Z]{26}\\.mp4$~', $media->path);
        $this->assertSame('video/mp4', $media->mime_type);
        Storage::disk('public')->assertExists($media->path);
    }

    public function test_it_rejects_a_file_whose_content_type_is_not_allowed(): void
    {
        $file = UploadedFile::fake()->createWithContent('illustration.svg', '<svg><script>alert(1)</script></svg>');

        try {
            app(MediaStorageService::class)->store($file);
            $this->fail('Une ValidationException était attendue.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('file', $exception->errors());
        }

        $this->assertDatabaseCount('media_assets', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_it_rejects_an_image_larger_than_the_configured_limit(): void
    {
        config(['maracuja-media.image_max_size_kb' => 1]);
        $file = UploadedFile::fake()->image('large.jpg')->size(2);

        $this->expectException(ValidationException::class);

        app(MediaStorageService::class)->store($file);
    }

    public function test_it_removes_the_stored_file_when_database_creation_fails(): void
    {
        MediaAsset::creating(function (): void {
            throw new \RuntimeException('Database failure for test.');
        });

        try {
            app(MediaStorageService::class)->store(UploadedFile::fake()->image('photo.jpg'));
            $this->fail('Une RuntimeException était attendue.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Database failure for test.', $exception->getMessage());
        }

        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }
}
