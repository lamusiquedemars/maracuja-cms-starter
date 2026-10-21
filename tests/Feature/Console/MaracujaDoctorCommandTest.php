<?php

namespace Tests\Feature\Console;

use App\Models\User;
use Maracuja\SiteCore\Foundation\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaracujaDoctorCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_doctor_passes_for_seeded_local_installation(): void
    {
        config([
            'app.key' => 'base64:test-key',
            'app.url' => 'http://localhost',
            'maracuja.offer' => 'signature',
            'maracuja.seo.indexable' => false,
        ]);

        SiteSetting::query()->create([
            'site_name' => 'Maracuja CMS',
            'contact_email' => 'contact@example.test',
        ]);

        User::factory()->create(['is_admin' => true]);

        $this->artisan('maracuja:doctor')
            ->expectsOutputToContain('L’installation semble saine.')
            ->assertExitCode(0);
    }

    public function test_doctor_fails_for_unknown_offer(): void
    {
        config([
            'app.key' => 'base64:test-key',
            'maracuja.offer' => 'mystery',
        ]);

        SiteSetting::query()->create([
            'site_name' => 'Maracuja CMS',
            'contact_email' => 'contact@example.test',
        ]);

        User::factory()->create(['is_admin' => true]);

        $this->artisan('maracuja:doctor')
            ->expectsOutputToContain('Offre inconnue')
            ->assertExitCode(1);
    }

    public function test_production_doctor_fails_when_site_is_noindex(): void
    {
        config([
            'app.key' => 'base64:test-key',
            'app.url' => 'https://example.test',
            'maracuja.offer' => 'signature',
            'maracuja.seo.indexable' => false,
        ]);

        SiteSetting::query()->create([
            'site_name' => 'Maracuja CMS',
            'contact_email' => 'contact@example.test',
        ]);

        User::factory()->create(['is_admin' => true]);

        $this->artisan('maracuja:doctor --production')
            ->expectsOutputToContain('Le site est configuré en noindex')
            ->assertExitCode(1);
    }
}
