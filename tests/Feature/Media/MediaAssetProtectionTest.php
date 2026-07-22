<?php

namespace Tests\Feature\Media;

use App\Models\User;
use App\Modules\Media\Enums\MediaType;
use App\Modules\Media\Exceptions\MediaAssetInUseException;
use App\Modules\Media\Models\MediaAsset;
use App\Modules\Media\Models\MediaUsage;
use App\Modules\Media\Policies\MediaAssetPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaAssetProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_an_unused_media_can_be_deleted(): void
    {
        $media = $this->media();
        Storage::disk('public')->put($media->path, 'image');

        $this->assertTrue($media->canBeDeleted());
        $this->assertTrue($media->delete());
        $this->assertDatabaseMissing('media_assets', ['id' => $media->id]);
        Storage::disk('public')->assertMissing($media->path);
    }

    public function test_a_used_media_cannot_be_deleted_directly(): void
    {
        $media = $this->media();
        MediaUsage::query()->create([
            'media_asset_id' => $media->id,
            'usable_type' => 'page',
            'usable_id' => 12,
            'field' => 'hero',
            'context' => '',
        ]);

        $this->assertFalse($media->canBeDeleted());
        $this->expectException(MediaAssetInUseException::class);

        $media->delete();
    }

    public function test_policy_allows_only_an_admin_to_delete_an_unused_media(): void
    {
        $media = $this->media();
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['is_admin' => false]);
        $policy = new MediaAssetPolicy;

        $this->assertTrue($policy->delete($admin, $media));
        $this->assertFalse($policy->delete($user, $media));

        MediaUsage::query()->create([
            'media_asset_id' => $media->id,
            'usable_type' => 'article',
            'usable_id' => 4,
            'field' => 'image',
            'context' => '',
        ]);

        $this->assertFalse($policy->delete($admin, $media));
    }

    private function media(): MediaAsset
    {
        return MediaAsset::query()->create([
            'type' => MediaType::Image,
            'disk' => 'public',
            'path' => 'media/images/2026/07/example.jpg',
            'original_name' => 'example.jpg',
            'display_name' => 'Example',
            'mime_type' => 'image/jpeg',
            'extension' => 'jpg',
            'size' => 1234,
            'width' => 1200,
            'height' => 800,
            'checksum' => str_repeat('a', 64),
        ]);
    }
}
