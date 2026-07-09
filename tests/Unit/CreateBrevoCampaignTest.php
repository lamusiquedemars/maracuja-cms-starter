<?php

namespace Tests\Unit;

use App\Modules\Audience\Models\AudienceBrevoSetting;
use App\Modules\Audience\Models\AudienceContact;
use App\Modules\Audience\Models\AudienceSegment;
use App\Modules\Audience\Models\SegmentMessage;
use App\Modules\Audience\Models\SegmentMessageDelivery;
use App\Modules\Audience\Services\BrevoAudienceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CreateBrevoCampaignTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_draft_brevo_campaign_from_a_segment_message(): void
    {
        Http::fake([
            'https://api.brevo.com/v3/contacts' => Http::response(['id' => 1], 201),
            'https://api.brevo.com/v3/emailCampaigns' => Http::response(['id' => 99], 201),
        ]);

        $setting = AudienceBrevoSetting::query()->create([
            'is_enabled' => true,
            'api_key_encrypted' => 'xkeysib-secret',
            'sender_name' => 'Maracuja Digital',
            'sender_email' => 'contact@maracujadigital.fr',
            'reply_to_email' => 'reply@maracujadigital.fr',
            'default_folder_id' => 12,
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
            'subject' => 'Fermeture estivale',
            'body' => '<p>Bonjour, l’atelier ferme cet été.</p>',
        ]);

        $campaignId = app(BrevoAudienceService::class)->createCampaign($message, $setting);

        $this->assertSame(99, $campaignId);
        $this->assertSame(SegmentMessage::STATUS_CREATED_IN_BREVO, $message->refresh()->status);
        $this->assertSame(99, $message->brevo_campaign_id);
        $this->assertSame('draft', $message->brevo_status);
        $this->assertSame('Fermeture estivale', $message->subject_snapshot);
        $this->assertSame('<p>Bonjour, l’atelier ferme cet été.</p>', $message->content_snapshot_html);
        $this->assertSame([
            'name' => 'Maracuja Digital',
            'email' => 'contact@maracujadigital.fr',
            'reply_to_email' => 'reply@maracujadigital.fr',
        ], $message->sender_snapshot);

        Http::assertSent(function ($request) use ($message): bool {
            return $request->method() === 'POST'
                && $request->url() === 'https://api.brevo.com/v3/emailCampaigns'
                && $request['name'] === 'Maracuja #' . $message->id . ' - Fermeture estivale'
                && $request['subject'] === 'Fermeture estivale'
                && $request['sender'] === [
                    'name' => 'Maracuja Digital',
                    'email' => 'contact@maracujadigital.fr',
                ]
                && $request['replyTo'] === 'reply@maracujadigital.fr'
                && $request['recipients'] === ['listIds' => [34]]
                && ! isset($request['tag'])
                && $request['htmlContent'] === '<p>Bonjour, l’atelier ferme cet été.</p>';
        });
    }

    public function test_it_does_not_create_a_brevo_campaign_for_smtp_messages(): void
    {
        Http::fake();

        $segment = AudienceSegment::query()->create(['name' => 'Clients']);

        $message = SegmentMessage::query()->create([
            'audience_segment_id' => $segment->id,
            'provider' => SegmentMessage::PROVIDER_SMTP_LWS,
            'subject' => 'Message SMTP',
            'body' => '<p>Bonjour</p>',
        ]);

        $this->expectExceptionMessage('Cette campagne n’utilise pas le canal Brevo.');

        app(BrevoAudienceService::class)->createCampaign($message);

        Http::assertNothingSent();
    }

    public function test_it_does_not_create_a_brevo_campaign_when_no_contact_can_be_synced(): void
    {
        Http::fake();

        AudienceBrevoSetting::query()->create([
            'is_enabled' => true,
            'api_key_encrypted' => 'xkeysib-secret',
            'sender_name' => 'Maracuja Digital',
            'sender_email' => 'contact@maracujadigital.fr',
            'default_folder_id' => 12,
        ]);

        $segment = AudienceSegment::query()->create([
            'name' => 'Clients',
            'brevo_list_id' => 34,
        ]);

        $contact = AudienceContact::query()->create([
            'email' => 'ivo@example.test',
            'accepts_email' => false,
        ]);

        $segment->contacts()->attach($contact);

        $message = SegmentMessage::query()->create([
            'audience_segment_id' => $segment->id,
            'provider' => SegmentMessage::PROVIDER_BREVO,
            'subject' => 'Message Brevo',
            'body' => '<p>Bonjour</p>',
        ]);

        try {
            app(BrevoAudienceService::class)->createCampaign($message);
            $this->fail('La campagne Brevo ne devrait pas être créée sans contact éligible.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('Aucun contact éligible', $exception->getMessage());
        }

        Http::assertNotSent(fn ($request): bool => $request->url() === 'https://api.brevo.com/v3/emailCampaigns');
    }

    public function test_switching_back_to_standard_delivery_clears_brevo_sync_error_state(): void
    {
        $segment = AudienceSegment::query()->create(['name' => 'Clients']);

        $message = SegmentMessage::query()->create([
            'audience_segment_id' => $segment->id,
            'provider' => SegmentMessage::PROVIDER_BREVO,
            'status' => SegmentMessage::STATUS_SYNC_FAILED,
            'brevo_status' => 'error',
            'brevo_error' => 'Erreur Brevo',
            'subject' => 'Message Brevo',
            'body' => '<p>Bonjour</p>',
        ]);

        $message->forceFill([
            'provider' => SegmentMessage::PROVIDER_SMTP_LWS,
        ])->save();

        $this->assertSame(SegmentMessage::STATUS_DRAFT, $message->refresh()->status);
        $this->assertNull($message->brevo_status);
        $this->assertNull($message->brevo_error);
    }

    public function test_it_shows_a_friendly_message_when_brevo_sender_is_inactive(): void
    {
        Http::fake([
            'https://api.brevo.com/v3/contacts' => Http::response(['id' => 1], 201),
            'https://api.brevo.com/v3/emailCampaigns' => Http::response([
                'code' => 'invalid_parameter',
                'message' => 'Sender is invalid / inactive',
            ], 400),
        ]);

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

        $message = SegmentMessage::query()->create([
            'audience_segment_id' => $segment->id,
            'provider' => SegmentMessage::PROVIDER_BREVO,
            'subject' => 'Fermeture estivale',
            'body' => '<p>Bonjour</p>',
        ]);

        try {
            app(BrevoAudienceService::class)->createCampaign($message);
            $this->fail('La campagne Brevo ne devrait pas être créée avec un expéditeur inactif.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('expéditeur configuré n’est pas encore validé', $exception->getMessage());
        }

        $this->assertSame(SegmentMessage::STATUS_SYNC_FAILED, $message->refresh()->status);
        $this->assertStringContainsString('Sender is invalid / inactive', $message->brevo_error);
    }

    public function test_it_sends_a_created_brevo_campaign_to_the_provider(): void
    {
        Http::fake([
            'https://api.brevo.com/v3/emailCampaigns/99/sendNow' => Http::response(null, 204),
        ]);

        AudienceBrevoSetting::query()->create([
            'is_enabled' => true,
            'api_key_encrypted' => 'xkeysib-secret',
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

        $message = SegmentMessage::query()->create([
            'audience_segment_id' => $segment->id,
            'provider' => SegmentMessage::PROVIDER_BREVO,
            'status' => SegmentMessage::STATUS_CREATED_IN_BREVO,
            'brevo_campaign_id' => 99,
            'brevo_status' => 'draft',
            'subject' => 'Fermeture estivale',
            'body' => '<p>Bonjour</p>',
        ]);

        app(BrevoAudienceService::class)->sendCampaign($message);

        $this->assertSame(SegmentMessage::STATUS_SENT_TO_PROVIDER, $message->refresh()->status);
        $this->assertSame('sent_to_provider', $message->brevo_status);
        $this->assertNotNull($message->brevo_sent_at);
        $this->assertSame(1, $message->recipients_count);

        $delivery = SegmentMessageDelivery::query()->firstOrFail();

        $this->assertSame(SegmentMessageDelivery::STATUS_SENT_TO_PROVIDER, $delivery->status);
        $this->assertSame('sent_to_provider', $delivery->latest_event);
        $this->assertSame('ivo@example.test', $delivery->email);
        $this->assertNotNull($contact->refresh()->last_contacted_at);

        $report = $message->deliveryReport();

        $this->assertSame(1, $report['sent_to_provider']);
        $this->assertSame(0, $report['delivered']);
        $this->assertSame(0, $report['opened']);
        $this->assertSame(0, $report['clicked']);

        Http::assertSent(fn ($request): bool => $request->method() === 'POST'
            && $request->url() === 'https://api.brevo.com/v3/emailCampaigns/99/sendNow');
    }

    public function test_it_requires_a_brevo_campaign_before_sending(): void
    {
        Http::fake();

        $segment = AudienceSegment::query()->create(['name' => 'Clients']);

        $message = SegmentMessage::query()->create([
            'audience_segment_id' => $segment->id,
            'provider' => SegmentMessage::PROVIDER_BREVO,
            'subject' => 'Message Brevo',
            'body' => '<p>Bonjour</p>',
        ]);

        $this->expectExceptionMessage('La campagne doit d’abord être créée dans Brevo.');

        app(BrevoAudienceService::class)->sendCampaign($message);

        Http::assertNothingSent();
    }

    public function test_it_blocks_brevo_campaign_creation_when_message_contains_local_images(): void
    {
        Http::fake();

        config(['app.url' => 'http://maracuja-cms.local']);

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

        $message = SegmentMessage::query()->create([
            'audience_segment_id' => $segment->id,
            'provider' => SegmentMessage::PROVIDER_BREVO,
            'subject' => 'Fermeture estivale',
            'body' => '<p><img src="/storage/logo.jpg"></p>',
        ]);

        try {
            app(BrevoAudienceService::class)->createCampaign($message);
            $this->fail('La campagne Brevo ne devrait pas être créée avec une image locale.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('images du message ne sont pas accessibles publiquement', $exception->getMessage());
        }

        $this->assertTrue($message->hasPublicImageWarnings());
        Http::assertNothingSent();
    }
}
