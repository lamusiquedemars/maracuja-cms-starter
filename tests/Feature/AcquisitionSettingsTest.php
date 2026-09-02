<?php

namespace Tests\Feature;

use App\Modules\Acquisition\Models\AcquisitionSetting;
use App\Support\Modules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AcquisitionSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_acquisition_is_installed_but_disabled_by_default(): void
    {
        $setting = AcquisitionSetting::current();

        $this->assertTrue(Modules::installed('acquisition'));
        $this->assertFalse(Modules::enabled('acquisition'));
        $this->assertFalse($setting->is_enabled);
        $this->assertTrue($setting->consent_enabled);
        $this->assertSame('basic', $setting->consent_mode);
        $this->assertSame('EUR', $setting->currency);
    }

    public function test_tracking_requires_a_valid_gtm_container(): void
    {
        $this->expectException(ValidationException::class);

        AcquisitionSetting::query()->create([
            'is_enabled' => true,
            'gtm_container_id' => 'G-ANALYTICS',
            'consent_enabled' => true,
            'consent_mode' => 'basic',
            'timezone' => 'Europe/Paris',
            'currency' => 'EUR',
        ]);
    }

    public function test_gtm_and_currency_are_normalized(): void
    {
        $setting = AcquisitionSetting::query()->create([
            'is_enabled' => true,
            'gtm_container_id' => ' gtm-abc1234 ',
            'consent_enabled' => true,
            'consent_mode' => 'basic',
            'timezone' => 'America/Cuiaba',
            'currency' => 'brl',
        ]);

        $this->assertSame('GTM-ABC1234', $setting->gtm_container_id);
        $this->assertSame('BRL', $setting->currency);
        $this->assertTrue($setting->hasValidContainer());
    }
}
