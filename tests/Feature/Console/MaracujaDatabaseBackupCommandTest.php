<?php

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaracujaDatabaseBackupCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_private_logical_snapshot(): void
    {
        $name = 'automated-test-'.uniqid();
        $path = storage_path("app/private/database-backups/{$name}.json");

        try {
            $this->artisan('maracuja:db:backup', ['--name' => $name])
                ->expectsOutputToContain('Snapshot de base créé')
                ->assertSuccessful();

            $this->assertFileExists($path);
            $snapshot = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
            $this->assertSame(1, $snapshot['version']);
            $this->assertArrayHasKey('users', $snapshot['tables']);
            $this->assertArrayHasKey('media_assets', $snapshot['tables']);
        } finally {
            @unlink($path);
        }
    }
}
