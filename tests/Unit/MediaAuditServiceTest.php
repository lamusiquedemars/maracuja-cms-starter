<?php

namespace Tests\Unit;

use App\Modules\Media\Services\MediaAuditService;
use Illuminate\Filesystem\Filesystem;
use Tests\TestCase;

class MediaAuditServiceTest extends TestCase
{
    private string $temporaryRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->temporaryRoot = sys_get_temp_dir().'/maracuja-media-audit-'.bin2hex(random_bytes(6));

        foreach (['public', 'legacy-public', 'private', 'code'] as $directory) {
            mkdir($this->temporaryRoot.'/'.$directory, 0777, true);
        }
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->temporaryRoot);

        parent::tearDown();
    }

    public function test_it_accepts_only_canonical_public_media_paths(): void
    {
        $this->write('public/media/images/2026/07/photo.jpg', 'canonical-image');
        $this->write('public/pages/hero.jpg', 'legacy-image');

        $report = $this->service()->audit(includeDatabase: false);

        $this->assertSame(2, $report['summary']['files']);
        $this->assertSame(1, $report['summary']['anomalies']);
        $this->assertSame('public_path_forbidden', $report['anomalies'][0]['type']);
    }

    public function test_it_detects_identical_files_across_storage_roots(): void
    {
        $this->write('public/media/images/2026/07/photo.jpg', 'same-content');
        $this->write('legacy-public/pages/photo.jpg', 'same-content');
        $this->write('private/pages/photo.jpg', 'same-content');

        $report = $this->service()->audit(includeDatabase: false);

        $this->assertCount(1, $report['duplicates']);
        $this->assertCount(3, $report['duplicates'][0]['paths']);
        $this->assertContains('legacy_public_file', array_column($report['anomalies'], 'type'));
    }

    public function test_it_allows_declared_private_work_directories(): void
    {
        $this->write('private/imports/audience/contacts.csv', "email\nivo@example.test");
        $this->write('private/livewire-tmp/upload.jpg', 'temporary-image');

        $report = $this->service()->audit(includeDatabase: false);

        $this->assertSame(0, $report['summary']['anomalies']);
    }

    public function test_it_detects_forbidden_upload_directories_in_code(): void
    {
        $this->write('code/ExampleResource.php', "<?php\nFileUpload::make('image')->directory('pages');");

        $report = $this->service()->audit(includeDatabase: false);

        $this->assertContains('forbidden_upload_directory', array_column($report['anomalies'], 'type'));
    }

    public function test_it_allows_private_import_directories_and_the_maracuja_rich_editor(): void
    {
        $this->write('code/ImportResource.php', "<?php\nFileUpload::make('csv')->directory('imports/audience');");
        $this->write('code/PageResource.php', "<?php\nMaracujaRichEditor::make('content');");

        $report = $this->service()->audit();
        $types = array_column($report['anomalies'], 'type');

        $this->assertNotContains('forbidden_upload_directory', $types);
        $this->assertNotContains('rich_editor_native_attachments', $types);
    }

    public function test_it_detects_dynamic_upload_directories_in_code(): void
    {
        $this->write('code/GalleryResource.php', "<?php\nFileUpload::make('image')->directory(fn () => \$this->galleryDirectory());");

        $report = $this->service()->audit(includeDatabase: false);

        $this->assertContains('forbidden_upload_directory', array_column($report['anomalies'], 'type'));
    }

    public function test_it_detects_rich_editors_without_the_maracuja_media_integration(): void
    {
        $this->write('code/MessageResource.php', "<?php\nRichEditor::make('body');");

        $report = $this->service()->audit(includeDatabase: false);

        $this->assertContains('rich_editor_native_attachments', array_column($report['anomalies'], 'type'));
    }

    private function service(): MediaAuditService
    {
        return new MediaAuditService([
            'public' => $this->temporaryRoot.'/public',
            'legacy_public' => $this->temporaryRoot.'/legacy-public',
            'private' => $this->temporaryRoot.'/private',
        ], $this->temporaryRoot.'/code');
    }

    private function write(string $relativePath, string $contents): void
    {
        $path = $this->temporaryRoot.'/'.$relativePath;

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        file_put_contents($path, $contents);
    }
}
