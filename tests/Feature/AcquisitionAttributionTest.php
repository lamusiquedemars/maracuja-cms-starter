<?php

namespace Tests\Feature;

use App\Modules\Inquiries\Models\Inquiry;
use App\Modules\SiteSettings\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AcquisitionAttributionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['maracuja.modules.acquisition' => true]);
        Mail::fake();
        SiteSetting::current()->update(['contact_email' => null]);
    }

    public function test_contact_form_stores_safe_campaign_attribution_without_tracking_being_enabled(): void
    {
        $this->post('/contact', [
            'name' => 'Camille Martin',
            'email' => 'camille@example.test',
            'message' => 'Je souhaite être recontactée.',
            'acquisition_attribution' => json_encode([
                'first_touch' => [
                    'utm_source' => 'google',
                    'utm_medium' => 'cpc',
                    'utm_campaign' => 'consultation-penale',
                    'gclid' => 'first-click',
                    'email' => 'must-not-be-saved@example.test',
                ],
                'last_touch' => [
                    'gclid' => 'last-click',
                    'landing_page' => '/contact?gclid=last-click',
                ],
            ], JSON_THROW_ON_ERROR),
        ])->assertRedirect('/contact');

        $inquiry = Inquiry::query()->sole();

        $this->assertSame('google', $inquiry->attribution_source);
        $this->assertSame('cpc', $inquiry->attribution_medium);
        $this->assertSame('consultation-penale', $inquiry->attribution_campaign);
        $this->assertSame('first-click', $inquiry->attribution_first_touch['gclid']);
        $this->assertSame('last-click', $inquiry->attribution_last_touch['gclid']);
        $this->assertArrayNotHasKey('email', $inquiry->attribution_first_touch);
        $this->assertSame('1.00', $inquiry->attribution_confidence);
    }

    public function test_contact_lead_is_queued_once_for_cremona_with_an_encrypted_payload(): void
    {
        config(['maracuja.acquisition.cremona' => [
            'enabled' => true,
            'endpoint' => 'https://cremona.example.test/api/v1/incoming-requests',
            'token' => 'site-integration-secret',
            'site_reference' => 'example-site',
        ]]);
        Http::fake(['cremona.example.test/*' => Http::response([], 201)]);

        $this->post('/contact', [
            'name' => 'Camille Martin', 'email' => 'camille@example.test',
            'message' => 'Je souhaite être recontactée.',
        ])->assertRedirect('/contact');

        $delivery = DB::table('acquisition_deliveries')->sole();
        $this->assertSame('sent', $delivery->status);
        $this->assertStringNotContainsString('camille@example.test', $delivery->payload);
    }

    public function test_a_connected_contact_form_does_not_create_a_local_inquiry(): void
    {
        config(['maracuja.acquisition.cremona' => ['enabled' => true, 'endpoint' => 'https://cremona.example.test/api/v1/incoming-requests', 'token' => 'site-integration-secret', 'site_reference' => 'example-site']]);
        Http::fake(['cremona.example.test/*' => Http::response([], 201)]);

        $this->post('/contact', ['name' => 'Camille Martin', 'email' => 'camille@example.test', 'message' => 'Bonjour.'])->assertRedirect('/contact');

        $this->assertDatabaseCount(Inquiry::class, 0);
        $this->assertDatabaseCount('acquisition_deliveries', 1);
    }

    public function test_a_temporary_cremona_failure_is_recorded_for_a_scheduled_retry(): void
    {
        config(['maracuja.acquisition.cremona' => ['enabled' => true, 'endpoint' => 'https://cremona.example.test/api/v1/incoming-requests', 'token' => 'site-integration-secret', 'site_reference' => 'example-site']]);
        Http::fake(['cremona.example.test/*' => Http::response([], 503)]);

        $this->post('/contact', ['name' => 'Camille Martin', 'email' => 'camille@example.test', 'message' => 'Bonjour.'])->assertRedirect('/contact');

        $this->assertDatabaseHas('acquisition_deliveries', [
            'status' => 'pending',
            'response_status' => 503,
            'attempts' => 1,
        ]);
    }
}
