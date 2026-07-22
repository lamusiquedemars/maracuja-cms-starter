<?php

namespace Tests\Feature\Console;

use App\Modules\Media\Services\MediaAuditService;
use Mockery\MockInterface;
use Tests\TestCase;

class MaracujaMediaAuditCommandTest extends TestCase
{
    public function test_command_fails_when_the_report_contains_anomalies(): void
    {
        $this->mock(MediaAuditService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('audit')->once()->with(true)->andReturn($this->report(anomalies: 1));
        });

        $this->artisan('maracuja:media:audit')
            ->expectsOutputToContain('Anomalies à traiter')
            ->assertExitCode(1);
    }

    public function test_command_passes_for_a_clean_report(): void
    {
        $this->mock(MediaAuditService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('audit')->once()->with(false)->andReturn($this->report());
        });

        $this->artisan('maracuja:media:audit --no-database')
            ->expectsOutputToContain('Le stockage média respecte le contrat Maracuja.')
            ->assertExitCode(0);
    }

    /** @return array<string, mixed> */
    private function report(int $anomalies = 0): array
    {
        return [
            'roots' => [],
            'files' => [],
            'duplicates' => [],
            'references' => [],
            'anomalies' => $anomalies === 0 ? [] : [[
                'type' => 'public_path_forbidden',
                'location' => 'public:pages/hero.jpg',
                'detail' => 'Chemin interdit.',
            ]],
            'summary' => [
                'files' => 0,
                'bytes' => 0,
                'duplicate_groups' => 0,
                'references' => 0,
                'anomalies' => $anomalies,
            ],
        ];
    }
}
