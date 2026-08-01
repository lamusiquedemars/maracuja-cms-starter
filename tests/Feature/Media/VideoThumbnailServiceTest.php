<?php

namespace Tests\Feature\Media;

use App\Modules\Media\Enums\MediaType;
use App\Modules\Media\Models\MediaAsset;
use App\Modules\Media\Services\VideoThumbnailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VideoThumbnailServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Carbon::setTestNow('2026-08-01 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_generates_and_records_a_canonical_video_thumbnail(): void
    {
        $media = $this->video();
        Storage::disk('public')->put($media->path, 'video');

        Process::fake(function (PendingProcess $process): int {
            Storage::disk('public')->put($this->relativePublicPath($process->command[array_key_last($process->command)]), 'jpeg');

            return 0;
        });

        $this->assertTrue(app(VideoThumbnailService::class)->generate($media));

        $media->refresh();
        $this->assertMatchesRegularExpression(
            '~^media/video-thumbnails/2026/08/[0-9A-HJKMNP-TV-Z]{26}\\.jpg$~',
            (string) $media->thumbnail_path,
        );
        Storage::disk('public')->assertExists($media->thumbnail_path);
        Process::assertRan(fn (PendingProcess $process): bool => in_array('-frames:v', $process->command, true));
    }

    public function test_it_skips_a_video_that_already_has_a_thumbnail(): void
    {
        Process::fake();
        $media = $this->video(['thumbnail_path' => 'media/video-thumbnails/existing.jpg']);

        $this->assertFalse(app(VideoThumbnailService::class)->generate($media));
        Process::assertNothingRan();
    }

    private function video(array $attributes = []): MediaAsset
    {
        return MediaAsset::query()->create(array_merge([
            'type' => MediaType::Video,
            'disk' => 'public',
            'path' => 'media/videos/2026/08/example.mp4',
            'original_name' => 'example.mp4',
            'display_name' => 'Example',
            'mime_type' => 'video/mp4',
            'extension' => 'mp4',
            'size' => 1234,
            'checksum' => str_repeat('a', 64),
        ], $attributes));
    }

    private function relativePublicPath(string $absolutePath): string
    {
        return ltrim(str_replace(Storage::disk('public')->path(''), '', $absolutePath), DIRECTORY_SEPARATOR);
    }
}
