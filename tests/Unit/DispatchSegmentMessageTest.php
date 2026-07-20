<?php

namespace Tests\Unit;

use App\Modules\Audience\Actions\DispatchSegmentMessage;
use App\Modules\Audience\Models\AudienceBrevoSetting;
use App\Modules\Audience\Models\AudienceContact;
use App\Modules\Audience\Models\AudienceSegment;
use App\Modules\Audience\Models\SegmentMessage;
use App\Modules\Audience\Models\SegmentMessageDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DispatchSegmentMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_now_uses_brevo_immediately_when_the_message_provider_is_brevo(): void
    {
        Http::fake([
            'https://api.brevo.com/v3/contacts' => Http::response(['id' => 1], 201),
            'https://api.brevo.com/v3/emailCampaigns' => Http::response(['id' => 99], 201),
            'https://api.brevo.com/v3/emailCampaigns/99/sendNow' => Http::response(null, 204),
        ]);

        $message = $this->brevoMessageFixture();

        $stats = DispatchSegmentMessage::run($message);

        $this->assertFalse($stats['scheduled']);
        $this->assertSame(1, $stats['sent']);
        $this->assertSame(SegmentMessage::STATUS_SENT_TO_PROVIDER, $message->refresh()->status);
        $this->assertSame(99, $message->brevo_campaign_id);

        Http::assertSent(fn ($request): bool => $request->method() === 'POST'
            && $request->url() === 'https://api.brevo.com/v3/emailCampaigns');
        Http::assertSent(fn ($request): bool => $request->method() === 'POST'
            && $request->url() === 'https://api.brevo.com/v3/emailCampaigns/99/sendNow');
    }

    public function test_scheduled_brevo_message_is_delegated_to_brevo_immediately(): void
    {
        Http::fake([
            'https://api.brevo.com/v3/contacts' => Http::response(['id' => 1], 201),
            'https://api.brevo.com/v3/emailCampaigns' => Http::response(['id' => 99], 201),
            'https://api.brevo.com/v3/emailCampaigns/99/status' => Http::response(null, 204),
        ]);

        $now = now();
        $this->travelTo($now);

        $message = $this->brevoMessageFixture();

        $stats = DispatchSegmentMessage::run($message, $now->copy()->addHour());

        $this->assertTrue($stats['scheduled']);
        $this->assertSame(1, $stats['queued']);
        $this->assertSame(1, $stats['processed']);
        $this->assertSame(SegmentMessage::STATUS_QUEUED, $message->refresh()->status);
        $this->assertSame('queued', $message->brevo_status);
        $this->assertSame(SegmentMessageDelivery::STATUS_SYNCED_TO_BREVO, $message->deliveries()->first()->status);

        Http::assertSent(fn ($request): bool => $request->method() === 'POST'
            && $request->url() === 'https://api.brevo.com/v3/emailCampaigns'
            && $request['scheduledAt'] === $now->copy()->addHour()->toIso8601String());
        Http::assertSent(fn ($request): bool => $request->method() === 'PUT'
            && $request->url() === 'https://api.brevo.com/v3/emailCampaigns/99/status'
            && $request['status'] === 'queued');
        Http::assertNotSent(fn ($request): bool => $request->url() === 'https://api.brevo.com/v3/emailCampaigns/99/sendNow');
    }

    private function brevoMessageFixture(): SegmentMessage
    {
        AudienceBrevoSetting::query()->create([
            'is_enabled' => true,
            'api_key_encrypted' => 'xkeysib-secret',
            'sender_name' => 'Maracuja Digital',
            'sender_email' => 'contact@maracujadigital.fr',
            'default_folder_id' => 12,
        ]);

        $segment = AudienceSegment::query()->create([
            'name' => 'Tous les clients',
            'brevo_list_id' => 34,
        ]);

        $contact = AudienceContact::query()->create([
            'email' => 'ivo@example.test',
            'accepts_email' => true,
        ]);

        $segment->contacts()->attach($contact);

        return SegmentMessage::query()->create([
            'audience_segment_id' => $segment->id,
            'provider' => SegmentMessage::PROVIDER_BREVO,
            'subject' => 'Fermeture estivale',
            'body' => '<p>Bonjour</p>',
        ]);
    }
}
