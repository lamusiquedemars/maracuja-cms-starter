<?php

namespace Tests\Unit;

use App\Modules\Audience\Actions\QueueSegmentMessage;
use App\Modules\Audience\Actions\SendPendingSegmentMessages;
use App\Modules\Audience\Mail\SegmentMessageMail;
use App\Modules\Audience\Models\AudienceContact;
use App\Modules\Audience\Models\AudienceSegment;
use App\Modules\Audience\Models\SegmentMessage;
use App\Modules\Audience\Models\SegmentMessageDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendSegmentMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_queues_and_sends_a_segment_message_to_eligible_contacts_only(): void
    {
        Mail::fake();

        $segment = AudienceSegment::query()->create([
            'name' => 'Locations violon',
        ]);

        $eligible = AudienceContact::query()->create([
            'first_name' => 'Ivo',
            'email' => 'ivo@example.test',
            'accepts_email' => true,
        ]);

        $refused = AudienceContact::query()->create([
            'first_name' => 'Ana',
            'email' => 'ana@example.test',
            'accepts_email' => false,
        ]);

        $unsubscribed = AudienceContact::query()->create([
            'first_name' => 'Lina',
            'email' => 'lina@example.test',
            'accepts_email' => true,
            'unsubscribed_at' => now(),
        ]);

        $segment->contacts()->attach([$eligible->id, $refused->id, $unsubscribed->id]);

        $message = SegmentMessage::query()->create([
            'audience_segment_id' => $segment->id,
            'subject' => 'Rappel location',
            'body' => 'Votre location arrive à échéance.',
        ]);

        $queuedCount = QueueSegmentMessage::run($message);

        $this->assertSame(1, $queuedCount);
        $this->assertSame('queued', $message->refresh()->status);
        $this->assertSame(1, $message->recipients_count);
        $this->assertNull($message->sent_at);
        $this->assertSame(1, SegmentMessageDelivery::query()->where('status', 'pending')->count());

        $stats = SendPendingSegmentMessages::run(limit: 16);

        $this->assertSame(1, $stats['sent']);
        $this->assertSame('sent', $message->refresh()->status);
        $this->assertNotNull($message->sent_at);
        $this->assertNotNull($eligible->refresh()->last_contacted_at);
        $this->assertNull($refused->refresh()->last_contacted_at);
        $this->assertNull($unsubscribed->refresh()->last_contacted_at);
        $this->assertSame(1, SegmentMessageDelivery::query()->count());
        $this->assertSame('sent', SegmentMessageDelivery::query()->first()->status);

        Mail::assertSent(SegmentMessageMail::class, 1);
    }

    public function test_it_does_not_queue_the_same_segment_message_twice(): void
    {
        Mail::fake();

        $segment = AudienceSegment::query()->create([
            'name' => 'Clients atelier',
        ]);

        $contact = AudienceContact::query()->create([
            'email' => 'ivo@example.test',
            'accepts_email' => true,
        ]);

        $segment->contacts()->attach($contact);

        $message = SegmentMessage::query()->create([
            'audience_segment_id' => $segment->id,
            'subject' => 'Information atelier',
            'body' => 'Message simple.',
        ]);

        QueueSegmentMessage::run($message);
        QueueSegmentMessage::run($message->refresh());

        $this->assertSame(1, SegmentMessageDelivery::query()->count());
        Mail::assertNothingSent();
    }

    public function test_it_keeps_message_as_draft_when_no_eligible_recipient_exists(): void
    {
        Mail::fake();

        $segment = AudienceSegment::query()->create([
            'name' => 'Segment vide',
        ]);

        $contact = AudienceContact::query()->create([
            'email' => 'ana@example.test',
            'accepts_email' => false,
        ]);

        $segment->contacts()->attach($contact);

        $message = SegmentMessage::query()->create([
            'audience_segment_id' => $segment->id,
            'subject' => 'Information',
            'body' => 'Message sans destinataire éligible.',
        ]);

        $queuedCount = QueueSegmentMessage::run($message);

        $this->assertSame(0, $queuedCount);
        $this->assertSame('draft', $message->refresh()->status);
        $this->assertSame(0, $message->refresh()->recipients_count);
        $this->assertNull($message->sent_at);
        $this->assertSame(0, SegmentMessageDelivery::query()->count());
        Mail::assertNothingSent();
    }

    public function test_it_respects_the_send_limit_per_cron_passage(): void
    {
        Mail::fake();

        $segment = AudienceSegment::query()->create([
            'name' => 'Fermeture estivale',
        ]);

        $contacts = collect(range(1, 20))->map(fn (int $index): AudienceContact => AudienceContact::query()->create([
            'email' => "client{$index}@example.test",
            'accepts_email' => true,
        ]));

        $segment->contacts()->attach($contacts->pluck('id'));

        $message = SegmentMessage::query()->create([
            'audience_segment_id' => $segment->id,
            'subject' => 'Fermeture estivale',
            'body' => 'Dates de fermeture.',
        ]);

        QueueSegmentMessage::run($message);

        $stats = SendPendingSegmentMessages::run(limit: 16);

        $this->assertSame(16, $stats['sent']);
        $this->assertSame(16, SegmentMessageDelivery::query()->where('status', 'sent')->count());
        $this->assertSame(4, SegmentMessageDelivery::query()->where('status', 'pending')->count());
        $this->assertSame('sending', $message->refresh()->status);
        Mail::assertSent(SegmentMessageMail::class, 16);
    }

    public function test_it_can_process_only_one_campaign_batch(): void
    {
        Mail::fake();

        $firstSegment = AudienceSegment::query()->create(['name' => 'Première campagne']);
        $secondSegment = AudienceSegment::query()->create(['name' => 'Deuxième campagne']);

        $firstContact = AudienceContact::query()->create([
            'email' => 'first@example.test',
            'accepts_email' => true,
        ]);

        $secondContact = AudienceContact::query()->create([
            'email' => 'second@example.test',
            'accepts_email' => true,
        ]);

        $firstSegment->contacts()->attach($firstContact);
        $secondSegment->contacts()->attach($secondContact);

        $firstMessage = SegmentMessage::query()->create([
            'audience_segment_id' => $firstSegment->id,
            'subject' => 'Message 1',
            'body' => 'Premier message.',
        ]);

        $secondMessage = SegmentMessage::query()->create([
            'audience_segment_id' => $secondSegment->id,
            'subject' => 'Message 2',
            'body' => 'Deuxième message.',
        ]);

        QueueSegmentMessage::run($firstMessage);
        QueueSegmentMessage::run($secondMessage);

        $stats = SendPendingSegmentMessages::runForMessage($secondMessage, limit: 25);

        $this->assertSame(1, $stats['sent']);
        $this->assertSame('pending', $firstMessage->deliveries()->first()->status);
        $this->assertSame('sent', $secondMessage->deliveries()->first()->status);
    }

    public function test_it_does_not_send_a_campaign_before_its_scheduled_date(): void
    {
        Mail::fake();
        $now = now();
        $this->travelTo($now);

        $segment = AudienceSegment::query()->create(['name' => 'Clients planifiés']);
        $contact = AudienceContact::query()->create([
            'email' => 'future@example.test',
            'accepts_email' => true,
        ]);
        $segment->contacts()->attach($contact);

        $message = SegmentMessage::query()->create([
            'audience_segment_id' => $segment->id,
            'subject' => 'Message futur',
            'body' => 'À envoyer plus tard.',
            'scheduled_at' => $now->copy()->addDay(),
        ]);

        QueueSegmentMessage::run($message);
        $stats = SendPendingSegmentMessages::run(limit: 16);

        $this->assertSame(0, $stats['processed']);
        $this->assertSame(SegmentMessage::STATUS_QUEUED, $message->refresh()->status);
        $this->assertSame(SegmentMessageDelivery::STATUS_PENDING, $message->deliveries()->first()->status);
        Mail::assertNothingSent();
    }

    public function test_it_sends_a_scheduled_campaign_once_the_date_has_arrived(): void
    {
        Mail::fake();
        $now = now();
        $this->travelTo($now);

        $segment = AudienceSegment::query()->create(['name' => 'Clients planifiés']);
        $contact = AudienceContact::query()->create([
            'email' => 'ready@example.test',
            'accepts_email' => true,
        ]);
        $segment->contacts()->attach($contact);

        $message = SegmentMessage::query()->create([
            'audience_segment_id' => $segment->id,
            'subject' => 'Message à échéance',
            'body' => 'À envoyer maintenant.',
            'scheduled_at' => $now->copy()->subMinute(),
        ]);

        QueueSegmentMessage::run($message);
        $stats = SendPendingSegmentMessages::run(limit: 16);

        $this->assertSame(1, $stats['sent']);
        $this->assertSame(SegmentMessage::STATUS_SENT, $message->refresh()->status);
        $this->assertSame(SegmentMessageDelivery::STATUS_SENT, $message->deliveries()->first()->status);
        Mail::assertSent(SegmentMessageMail::class, 1);
    }

    public function test_campaign_report_uses_client_facing_delivery_labels(): void
    {
        $segment = AudienceSegment::query()->create(['name' => 'Clients']);

        $accepted = AudienceContact::query()->create([
            'email' => 'accepted@example.test',
            'accepts_email' => true,
        ]);

        $refused = AudienceContact::query()->create([
            'email' => 'refused@example.test',
            'accepts_email' => false,
        ]);

        $segment->contacts()->attach([$accepted->id, $refused->id]);

        $message = SegmentMessage::query()->create([
            'audience_segment_id' => $segment->id,
            'subject' => 'Information',
            'body' => 'Message.',
        ]);

        QueueSegmentMessage::run($message);

        $delivery = $message->deliveries()->first();
        $delivery->forceFill([
            'status' => SegmentMessageDelivery::STATUS_SENT,
            'sent_at' => now(),
        ])->save();

        $report = $message->deliveryReport();

        $this->assertSame(2, $report['targeted']);
        $this->assertSame(1, $report['accepted']);
        $this->assertSame(1, $report['excluded']);
        $this->assertSame('Remis au serveur mail', $delivery->refresh()->statusLabel());
    }
}
