<?php

namespace Tests\Feature\Media;

use App\Modules\Media\Enums\MediaType;
use App\Modules\Media\Models\MediaAsset;
use App\Modules\Media\Services\MediaAuditService;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MediaAuditCatalogTest extends TestCase
{
    use RefreshDatabase;

    private string $temporaryRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->temporaryRoot = sys_get_temp_dir().'/maracuja-media-catalog-audit-'.bin2hex(random_bytes(6));

        foreach (['public/media/images/2026/07', 'legacy-public', 'private', 'code'] as $directory) {
            mkdir($this->temporaryRoot.'/'.$directory, 0777, true);
        }
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->temporaryRoot);

        parent::tearDown();
    }

    public function test_it_reports_files_without_catalog_and_catalog_entries_without_files(): void
    {
        file_put_contents($this->temporaryRoot.'/public/media/images/2026/07/orphan.jpg', 'orphan');
        MediaAsset::query()->create([
            'type' => MediaType::Image,
            'disk' => 'public',
            'path' => 'media/images/2026/07/missing.jpg',
            'original_name' => 'missing.jpg',
            'display_name' => 'Missing',
            'mime_type' => 'image/jpeg',
            'extension' => 'jpg',
            'size' => 100,
            'checksum' => str_repeat('b', 64),
        ]);

        $report = $this->service()->audit();
        $types = array_column($report['anomalies'], 'type');

        $this->assertContains('file_without_catalog', $types);
        $this->assertContains('catalog_without_file', $types);
    }

    private function service(): MediaAuditService
    {
        return new MediaAuditService([
            'public' => $this->temporaryRoot.'/public',
            'legacy_public' => $this->temporaryRoot.'/legacy-public',
            'private' => $this->temporaryRoot.'/private',
        ], $this->temporaryRoot.'/code');
    }
}
