<?php

namespace Tests\Feature\Media;

use App\Modules\Articles\Models\Article;
use App\Modules\Media\Enums\MediaType;
use App\Modules\Media\Exceptions\MediaAssetInUseException;
use App\Modules\Media\Models\MediaAsset;
use App\Modules\Pages\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaUsageTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_a_structured_media_relation_is_tracked_and_protected(): void
    {
        $media = $this->media('central.jpg');
        $page = Page::query()->create([
            'title' => 'Accueil',
            'slug' => 'accueil-test',
            'hero_media_id' => $media->id,
        ]);

        $this->assertDatabaseHas('media_usages', [
            'media_asset_id' => $media->id,
            'usable_type' => $page->getMorphClass(),
            'usable_id' => $page->id,
            'field' => 'hero_media_id',
        ]);
        $this->expectException(MediaAssetInUseException::class);

        $media->delete();
    }

    public function test_replacing_a_media_relation_releases_the_previous_media(): void
    {
        $first = $this->media('first.jpg');
        $second = $this->media('second.jpg');
        $page = Page::query()->create([
            'title' => 'Page',
            'slug' => 'page-test',
            'hero_media_id' => $first->id,
        ]);

        $page->update(['hero_media_id' => $second->id]);

        $this->assertTrue($first->fresh()->canBeDeleted());
        $this->assertFalse($second->fresh()->canBeDeleted());
        $this->assertDatabaseCount('media_usages', 1);
    }

    public function test_article_block_media_are_tracked_with_their_position(): void
    {
        $media = $this->media('block.jpg');
        $article = Article::query()->create([
            'title' => 'Article',
            'slug' => 'article-test',
            'body_blocks' => [
                ['type' => 'rich_text', 'text' => '<p>Texte</p>'],
                ['type' => 'image', 'media_id' => $media->id],
            ],
        ]);

        $this->assertDatabaseHas('media_usages', [
            'media_asset_id' => $media->id,
            'usable_type' => $article->getMorphClass(),
            'usable_id' => $article->id,
            'field' => 'body_blocks',
            'context' => 'block:1',
        ]);
    }

    public function test_central_media_url_has_priority_and_legacy_path_remains_a_fallback(): void
    {
        $media = $this->media('central.jpg');
        $page = Page::query()->create([
            'title' => 'Page média',
            'slug' => 'page-media-test',
            'hero_image_path' => 'pages/legacy.jpg',
            'hero_media_id' => $media->id,
        ]);

        $this->assertStringEndsWith('/storage/'.$media->path, $page->heroImageUrl());

        $page->update(['hero_media_id' => null]);

        $this->assertStringEndsWith('/storage/pages/legacy.jpg', $page->heroImageUrl());
    }

    private function media(string $name): MediaAsset
    {
        return MediaAsset::query()->create([
            'type' => MediaType::Image,
            'disk' => 'public',
            'path' => 'media/images/2026/07/'.$name,
            'original_name' => $name,
            'display_name' => $name,
            'mime_type' => 'image/jpeg',
            'extension' => 'jpg',
            'size' => 1234,
            'width' => 1200,
            'height' => 800,
            'checksum' => hash('sha256', $name),
        ]);
    }
}
