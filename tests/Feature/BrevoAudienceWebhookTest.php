<?php

namespace Tests\Feature;

use App\Modules\Audience\Models\AudienceBrevoEvent;
use App\Modules\Audience\Models\AudienceBrevoSetting;
use App\Modules\Audience\Models\AudienceContact;
use App\Modules\Audience\Models\AudienceSegment;
use App\Modules\Audience\Models\SegmentMessage;
use App\Modules\Audience\Models\SegmentMessageDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrevoAudienceWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_rejects_webhook_events_with_an_invalid_secret(): void
    {
        AudienceBrevoSetting::query()->create([
            'is_enabled' => true,
            'webhook_secret' => 'valid-secret',
        ]);

        $response = $this->postJson('/webhooks/brevo/audience/wrong-secret', [
            'event' => 'delivered',
            'email' => 'ivo@example.test',
            'camp_id' => 123,
        ]);

        $response->assertNotFound();
        $this->assertSame(0, AudienceBrevoEvent::query()->count());
    }

    public function test_it_marks_a_brevo_delivery_as_delivered(): void
    {
        [$message, $contact, $delivery] = $this->campaignDeliveryFixture();

        $response = $this->postJson('/webhooks/brevo/audience/webhook-secret', [
            'event' => 'delivered',
            'email' => 'IVO@example.test',
            'camp_id' => 123,
            'ts_event' => 1783584000,
            'id' => 'event-delivered-1',
        ]);

        $response->assertOk()->assertJson(['ok' => true]);

        $delivery->refresh();
        $this->assertSame(SegmentMessageDelivery::STATUS_DELIVERED, $delivery->status);
        $this->assertSame('delivered', $delivery->provider_status);
        $this->assertSame('delivered', $delivery->latest_event);
        $this->assertSame('event-delivered-1', $delivery->brevo_raw_event_id);
        $this->assertNotNull($delivery->delivered_at);

        $event = AudienceBrevoEvent::query()->first();
        $this->assertSame($message->id, $event->segment_message_id);
        $this->assertSame($delivery->id, $event->segment_message_delivery_id);
        $this->assertSame($contact->id, $event->audience_contact_id);
        $this->assertSame(123, $event->brevo_campaign_id);
        $this->assertSame('ivo@example.test', $event->email);
        $this->assertSame('delivered', $event->event_type);
        $this->assertNotNull($event->processed_at);
    }

    public function test_open_and_click_events_keep_the_highest_engagement_status(): void
    {
        [, , $delivery] = $this->campaignDeliveryFixture();

        $this->postJson('/webhooks/brevo/audience/webhook-secret', [
            'event' => 'click',
            'email' => 'ivo@example.test',
            'camp_id' => 123,
            'ts_event' => 1783584100,
        ])->assertOk();

        $this->postJson('/webhooks/brevo/audience/webhook-secret', [
            'event' => 'opened',
            'email' => 'ivo@example.test',
            'camp_id' => 123,
            'ts_event' => 1783584200,
        ])->assertOk();

        $delivery->refresh();
        $this->assertSame(SegmentMessageDelivery::STATUS_CLICKED, $delivery->status);
        $this->assertSame('opened', $delivery->latest_event);
        $this->assertNotNull($delivery->clicked_at);
        $this->assertNotNull($delivery->opened_at);
        $this->assertSame(2, AudienceBrevoEvent::query()->count());
    }

    public function test_it_marks_hard_bounced_contacts_as_no_longer_eligible(): void
    {
        [, $contact, $delivery] = $this->campaignDeliveryFixture();

        $this->postJson('/webhooks/brevo/audience/webhook-secret', [
            'event' => 'hard_bounce',
            'email' => 'ivo@example.test',
            'camp_id' => 123,
            'reason' => 'Mailbox does not exist',
            'ts_event' => 1783584300,
        ])->assertOk();

        $delivery->refresh();
        $contact->refresh();

        $this->assertSame(SegmentMessageDelivery::STATUS_HARD_BOUNCED, $delivery->status);
        $this->assertSame('Mailbox does not exist', $delivery->bounce_reason);
        $this->assertNotNull($delivery->hard_bounced_at);
        $this->assertNotNull($contact->hard_bounced_at);
        $this->assertSame('Mailbox does not exist', $contact->last_bounce_reason);
        $this->assertFalse($contact->canReceiveSegmentEmail());
    }

    public function test_it_applies_unsubscribe_events_to_the_contact(): void
    {
        [, $contact, $delivery] = $this->campaignDeliveryFixture();

        $this->postJson('/webhooks/brevo/audience/webhook-secret', [
            'event' => 'unsubscribe',
            'email' => 'ivo@example.test',
            'camp_id' => 123,
            'ts_event' => 1783584400,
        ])->assertOk();

        $delivery->refresh();
        $contact->refresh();

        $this->assertSame(SegmentMessageDelivery::STATUS_UNSUBSCRIBED, $delivery->status);
        $this->assertNotNull($delivery->unsubscribed_at);
        $this->assertFalse($contact->accepts_email);
        $this->assertNotNull($contact->unsubscribed_at);
        $this->assertFalse($contact->canReceiveSegmentEmail());
    }

    public function test_it_stores_unknown_events_for_later_audit(): void
    {
        $this->campaignDeliveryFixture();

        $this->postJson('/webhooks/brevo/audience/webhook-secret', [
            'event' => 'mystery',
            'email' => 'ivo@example.test',
            'camp_id' => 123,
        ])->assertOk();

        $this->assertDatabaseHas('audience_brevo_events', [
            'event_type' => 'mystery',
            'email' => 'ivo@example.test',
            'brevo_campaign_id' => 123,
        ]);
    }

    /**
     * @return array{SegmentMessage, AudienceContact, SegmentMessageDelivery}
     */
    private function campaignDeliveryFixture(): array
    {
        AudienceBrevoSetting::query()->create([
            'is_enabled' => true,
            'webhook_secret' => 'webhook-secret',
        ]);

        $segment = AudienceSegment::query()->create([
            'name' => 'Tous les clients',
            'brevo_list_id' => 34,
        ]);

        $contact = AudienceContact::query()->create([
            'first_name' => 'Ivo',
            'email' => 'ivo@example.test',
            'accepts_email' => true,
        ]);

        $segment->contacts()->attach($contact);

        $message = SegmentMessage::query()->create([
            'audience_segment_id' => $segment->id,
            'provider' => SegmentMessage::PROVIDER_BREVO,
            'status' => SegmentMessage::STATUS_SENT_TO_PROVIDER,
            'brevo_campaign_id' => 123,
            'brevo_status' => 'sent_to_provider',
            'subject' => 'Fermeture estivale',
            'body' => '<p>Bonjour</p>',
        ]);

        $delivery = SegmentMessageDelivery::query()->create([
            'segment_message_id' => $message->id,
            'audience_contact_id' => $contact->id,
            'email' => $contact->email,
            'status' => SegmentMessageDelivery::STATUS_SENT_TO_PROVIDER,
            'provider_status' => 'sent_to_provider',
            'latest_event' => 'sent_to_provider',
            'latest_event_at' => now(),
            'sent_at' => now(),
        ]);

        return [$message, $contact, $delivery];
    }
}
