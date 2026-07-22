<?php

namespace Tests\Unit;

use App\Modules\Media\Services\MediaAuditService;
use App\Modules\Media\Services\MediaMigrationPlanner;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class MediaMigrationPlannerTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = sys_get_temp_dir().'/maracuja-media-plan-'.uniqid();
        mkdir($this->root.'/public/pages', 0777, true);
        mkdir($this->root.'/legacy/pages', 0777, true);
        mkdir($this->root.'/private/imports/audience', 0777, true);
    }

    protected function tearDown(): void
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($this->root);
        parent::tearDown();
    }

    public function test_it_groups_identical_media_and_excludes_private_imports(): void
    {
        $image = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
        file_put_contents($this->root.'/public/pages/photo.png', $image);
        file_put_contents($this->root.'/legacy/pages/copy.png', $image);
        file_put_contents($this->root.'/private/imports/audience/list.csv', "email\nivo@example.test\n");

        $roots = [
            'public' => $this->root.'/public',
            'legacy_public' => $this->root.'/legacy',
            'private' => $this->root.'/private',
        ];
        App::instance(MediaAuditService::class, new MediaAuditService($roots, $this->root.'/empty-code'));

        $plan = (new MediaMigrationPlanner($roots))->plan();

        $this->assertSame(1, $plan['summary']['unique_media']);
        $this->assertSame(1, $plan['summary']['duplicate_copies']);
        $this->assertSame(1, $plan['summary']['excluded_files']);
        $this->assertSame('public:pages/photo.png', $plan['entries'][0]['source']);
        $this->assertStringStartsWith('media/images/', $plan['entries'][0]['destination']);
        $this->assertSame('private_technical_file', $plan['excluded'][0]['reason']);
    }
}
