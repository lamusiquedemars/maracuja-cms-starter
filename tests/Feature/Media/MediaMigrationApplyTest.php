<?php

namespace Tests\Feature\Media;

use App\Modules\Media\Services\MediaAuditService;
use App\Modules\Media\Services\MediaMigrationPlanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaMigrationApplyTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_plan_can_be_applied_without_removing_sources_and_rolled_back(): void
    {
        Storage::fake('public');
        $root = sys_get_temp_dir().'/maracuja-media-apply-'.uniqid();
        mkdir($root.'/public/pages', 0777, true);
        mkdir($root.'/legacy', 0777, true);
        mkdir($root.'/private', 0777, true);
        $source = $root.'/public/pages/photo.png';
        file_put_contents($source, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='));
        $roots = ['public' => $root.'/public', 'legacy_public' => $root.'/legacy', 'private' => $root.'/private'];
        App::instance(MediaAuditService::class, new MediaAuditService($roots, $root.'/code'));
        $planner = new MediaMigrationPlanner($roots);
        $name = 'test-'.uniqid().'.json';

        try {
            $planner->writeManifest($planner->plan(), $name);
            $applied = $planner->apply($name);
            $entry = $applied['entries'][0];

            $this->assertSame('applied', $applied['status']);
            $this->assertFileExists($source);
            $this->assertDatabaseHas('media_assets', ['id' => $entry['media_asset_id'], 'path' => $entry['destination']]);
            Storage::disk('public')->assertExists($entry['destination']);

            $cleaned = $planner->cleanup($name);

            $this->assertSame('cleaned', $cleaned['status']);
            $this->assertFileDoesNotExist($source);

            $rolledBack = $planner->rollback($name);

            $this->assertSame('rolled_back', $rolledBack['status']);
            $this->assertDatabaseCount('media_assets', 0);
            Storage::disk('public')->assertMissing($entry['destination']);
            $this->assertFileExists($source);
        } finally {
            @unlink($planner->manifestPath($name));
            @unlink($source);
            @rmdir($root.'/public/pages');
            @rmdir($root.'/public');
            @rmdir($root.'/legacy');
            @rmdir($root.'/private');
            @rmdir($root);
        }
    }
}
