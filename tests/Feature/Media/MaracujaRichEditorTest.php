<?php

namespace Tests\Feature\Media;

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
