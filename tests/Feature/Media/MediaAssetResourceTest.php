<?php

namespace Tests\Feature\Media;

use App\Models\User;
use App\Modules\Media\Enums\MediaType;
use App\Modules\Media\Filament\Resources\MediaAssets\MediaAssetResource;
use App\Modules\Media\Filament\Resources\MediaAssets\Pages\ManageMediaAssets;
use App\Modules\Media\Models\MediaAsset;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class MediaAssetResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_an_administrator_can_open_the_media_library_and_see_a_media_card(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_admin' => true]);
        $media = MediaAsset::query()->create([
            'type' => MediaType::Image,
            'disk' => 'public',
            'path' => 'media/images/2026/07/atelier.jpg',
            'original_name' => 'atelier-original.jpg',
            'display_name' => 'Portrait de l’atelier',
            'mime_type' => 'image/jpeg',
            'extension' => 'jpg',
            'size' => 2048,
            'width' => 1200,
            'height' => 800,
            'checksum' => str_repeat('c', 64),
        ]);
        Storage::disk('public')->put($media->path, 'image');

        $this->actingAs($admin)
            ->get(MediaAssetResource::getUrl('index'))
            ->assertOk()
            ->assertSee('Médias')
            ->assertSee('Portrait de l’atelier')
            ->assertSee('atelier-original.jpg');
    }

    public function test_an_administrator_can_upload_an_image_from_the_media_library(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_admin' => true]);
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(ManageMediaAssets::class)
            ->callAction('uploadMedia', [
                'files' => [UploadedFile::fake()->image('nouvelle-image.jpg', 640, 480)],
            ])
            ->assertHasNoActionErrors();

        $media = MediaAsset::query()->sole();

        $this->assertSame('nouvelle-image.jpg', $media->original_name);
        $this->assertSame($admin->id, $media->uploaded_by);
        Storage::disk('public')->assertExists($media->path);
    }
}
