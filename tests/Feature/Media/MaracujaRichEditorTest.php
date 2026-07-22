<?php

namespace Tests\Feature\Media;

use App\Modules\Audience\Models\AudienceSegment;
use App\Modules\Audience\Models\SegmentMessage;
use App\Modules\Media\Enums\MediaType;
use App\Modules\Media\Filament\Forms\Components\MaracujaRichEditor;
use App\Modules\Media\Models\MediaAsset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MaracujaRichEditorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_the_editor_exposes_the_media_tool_without_native_uploads(): void
    {
        $editor = MaracujaRichEditor::make('body');
        $reflection = new \ReflectionObject($editor);
        $tools = $reflection->getProperty('tools')->getValue($editor);
        $actions = $reflection->getProperty('actions')->getValue($editor);

        $this->assertContains('insertImage', collect($tools)->map->getName()->all());
        $this->assertContains('insertDocument', collect($tools)->map->getName()->all());
        $this->assertContains('insertImage', collect($actions)->map->getName()->all());
        $this->assertContains('insertDocument', collect($actions)->map->getName()->all());
        $this->assertFalse($reflection->getProperty('hasFileAttachments')->getValue($editor));
    }

    public function test_message_images_and_document_links_are_tracked(): void
    {
        $image = $this->media(MediaType::Image, 'photo.jpg', 'image/jpeg');
        $document = $this->media(MediaType::Document, 'catalogue.pdf', 'application/pdf');
        $segment = AudienceSegment::query()->create(['name' => 'Clients']);
        $message = SegmentMessage::query()->create([
            'audience_segment_id' => $segment->id,
            'provider' => SegmentMessage::PROVIDER_SMTP_LWS,
            'subject' => 'Nouveautés',
            'body' => '<p>Bonjour</p><img id="media-'.$image->id.'" src="'.$image->url().'" alt="Atelier">'
                .'<p><a href="'.$document->url().'">Télécharger la grille tarifaire</a></p>',
        ]);

        $this->assertDatabaseHas('media_usages', ['media_asset_id' => $image->id, 'field' => 'body']);
        $this->assertDatabaseHas('media_usages', ['media_asset_id' => $document->id, 'field' => 'body']);
        $this->assertFalse($image->fresh()->canBeDeleted());
        $this->assertFalse($document->fresh()->canBeDeleted());
    }

    private function media(MediaType $type, string $name, string $mime): MediaAsset
    {
        $directory = $type === MediaType::Image ? 'images' : 'documents';

        return MediaAsset::query()->create([
            'type' => $type,
            'disk' => 'public',
            'path' => "media/{$directory}/2026/07/{$name}",
            'original_name' => $name,
            'display_name' => $name,
            'mime_type' => $mime,
            'extension' => pathinfo($name, PATHINFO_EXTENSION),
            'size' => 1234,
            'width' => $type === MediaType::Image ? 1200 : null,
            'height' => $type === MediaType::Image ? 800 : null,
            'checksum' => hash('sha256', $name),
        ]);
    }
}
