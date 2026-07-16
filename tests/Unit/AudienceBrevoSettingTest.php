<?php

namespace Tests\Unit;

use App\Modules\Audience\Models\AudienceBrevoSetting;
use App\Modules\Audience\Services\BrevoAudienceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AudienceBrevoSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stores_the_brevo_api_key_encrypted(): void
    {
        $setting = AudienceBrevoSetting::query()->create([
            'api_key_encrypted' => 'xkeysib-secret',
        ]);

        $rawValue = DB::table('audience_brevo_settings')
            ->where('id', $setting->id)
            ->value('api_key_encrypted');

        $this->assertNotSame('xkeysib-secret', $rawValue);
        $this->assertSame('xkeysib-secret', $setting->refresh()->api_key_encrypted);
        $this->assertNotEmpty($setting->webhook_secret);
    }

    public function test_it_tests_brevo_connection_with_the_stored_api_key(): void
    {
        Http::fake([
            'https://api.brevo.com/v3/account' => Http::response(['email' => 'admin@example.test']),
        ]);

        $setting = AudienceBrevoSetting::query()->create([
            'api_key_encrypted' => 'xkeysib-secret',
        ]);

        $result = app(BrevoAudienceService::class)->testConnection($setting);

        $this->assertTrue($result['ok']);
        $this->assertSame(AudienceBrevoSetting::TEST_STATUS_SUCCESS, $result['status']);

        Http::assertSent(fn ($request): bool => $request->hasHeader('api-key', 'xkeysib-secret'));
    }
}
