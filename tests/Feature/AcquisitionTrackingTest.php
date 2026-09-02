<?php

namespace Tests\Feature;

use App\Modules\Acquisition\Models\AcquisitionSetting;
use App\Modules\SiteSettings\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcquisitionTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        config(['maracuja.modules.acquisition' => true]);
        SiteSetting::current();
    }

    public function test_disabled_tracking_captures_local_attribution_without_exposing_a_container(): void
    {
        AcquisitionSetting::current();

        $this->get('/')
            ->assertOk()
            ->assertSee('MaracujaAcquisitionConfig', false)
            ->assertSee('trackingEnabled', false)
            ->assertDontSee('googletagmanager.com', false);
    }

    public function test_basic_consent_prepares_gtm_without_loading_it_immediately(): void
    {
        AcquisitionSetting::current()->update([
            'is_enabled' => true,
            'gtm_container_id' => 'GTM-ABC1234',
            'consent_enabled' => true,
            'consent_mode' => 'basic',
        ]);

        $response = $this->get('/')->assertOk();

        $response
            ->assertSee('GTM-ABC1234', false)
            ->assertSee("analytics_storage: 'denied'", false)
            ->assertSee('window.maracujaLoadGtm', false)
            ->assertSee('data-acquisition-consent-banner', false)
            ->assertSee('data-acquisition-consent="granted"', false)
            ->assertDontSee('<noscript>', false);

        $this->assertSame(1, substr_count($response->getContent(), 'window.maracujaLoadGtm();'));
    }

    public function test_tracking_without_consent_gate_loads_gtm_and_noscript(): void
    {
        AcquisitionSetting::current()->update([
            'is_enabled' => true,
            'gtm_container_id' => 'GTM-ABC1234',
            'consent_enabled' => false,
            'consent_mode' => 'basic',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('window.maracujaLoadGtm();', false)
            ->assertSee('<noscript>', false)
            ->assertSee('ns.html?id=GTM-ABC1234', false);
    }
}
