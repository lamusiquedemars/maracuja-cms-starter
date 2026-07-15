<?php

namespace App\Modules\Audience\Services;

use App\Modules\Audience\Models\AudienceBrevoEvent;
use App\Modules\Audience\Models\AudienceContact;
use App\Modules\Audience\Models\SegmentMessage;
use App\Modules\Audience\Models\SegmentMessageDelivery;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class BrevoWebhookEventHandler
{
    public function handle(array $payload): AudienceBrevoEvent
    {
        $eventType = $this->eventType($payload);
        $email = $this->email($payload);
        $campaignId = $this->campaignId($payload);
        $eventDate = $this->eventDate($payload);

        $message = $campaignId
            ? SegmentMessage::query()->where('brevo_campaign_id', $campaignId)->first()
            : null;

        $contact = $email ? $this->findContactByEmail($email) : null;
        $delivery = $this->findDelivery($message, $contact, $email);

        $event = AudienceBrevoEvent::query()->create([
            'segment_message_id' => $message?->id,
            'segment_message_delivery_id' => $delivery?->id,
            'audience_contact_id' => $contact?->id,
            'brevo_campaign_id' => $campaignId,
            'email' => $email,
            'event_type' => $eventType,
            'event_date' => $eventDate,
            'raw_payload' => $payload,
        ]);

        if ($delivery) {
            $this->applyToDelivery($delivery, $eventType, $eventDate, $payload);
            $event->forceFill(['segment_message_delivery_id' => $delivery->id])->save();
        }

        if ($contact) {
            $this->applyToContact($contact, $eventType, $eventDate, $payload);
        }

        $event->forceFill([
            'processed_at' => now(),
            'audience_contact_id' => $contact?->id,
        ])->save();

        return $event->refresh();
    }

    private function eventType(array $payload): string
    {
        return Str::lower((string) ($payload['event'] ?? 'unknown'));
    }

    private function email(array $payload): ?string
    {
        $email = trim((string) ($payload['email'] ?? ''));

        return $email !== '' ? Str::lower($email) : null;
    }

    private function campaignId(array $payload): ?int
    {
        $value = $payload['camp_id'] ?? $payload['campaign_id'] ?? $payload['campaignId'] ?? null;

        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    private function eventDate(array $payload): CarbonImmutable
    {
        foreach (['ts_event', 'ts', 'date_event', 'date'] as $key) {
            $value = $payload[$key] ?? null;

            if (is_numeric($value)) {
                return CarbonImmutable::createFromTimestamp((int) $value);
            }

            if (is_string($value) && $value !== '') {
                return CarbonImmutable::parse($value);
            }
        }

        return CarbonImmutable::now();
    }

    private function findContactByEmail(string $email): ?AudienceContact
    {
        return AudienceContact::query()
            ->whereRaw('lower(email) = ?', [Str::lower($email)])
            ->first();
    }

    private function findDelivery(
        ?SegmentMessage $message,
        ?AudienceContact $contact,
        ?string $email,
    ): ?SegmentMessageDelivery {
        if (! $message) {
            return null;
        }

        $query = $message->deliveries();

        if ($contact) {
            $delivery = (clone $query)->where('audience_contact_id', $contact->id)->first();

            if ($delivery) {
                return $delivery;
            }
        }

        if ($email) {
            return (clone $query)
                ->whereRaw('lower(email) = ?', [Str::lower($email)])
                ->first();
        }

        return null;
    }

    private function applyToDelivery(
        SegmentMessageDelivery $delivery,
        string $eventType,
        CarbonImmutable $eventDate,
        array $payload,
    ): void {
        $status = $this->statusForEvent($eventType);
        $updates = [
            'provider_status' => $eventType,
            'latest_event' => $eventType,
            'latest_event_at' => $eventDate,
            'brevo_raw_event_id' => $this->rawEventId($payload),
        ];

        if ($status && $this->shouldApplyStatus($delivery->status, $status)) {
            $updates['status'] = $status;
        }

        foreach ($this->timestampColumns($eventType) as $column) {
            $updates[$column] = $delivery->{$column} ?? $eventDate;
        }

        $reason = $this->reason($payload);

        if ($reason !== null && in_array($eventType, ['soft_bounce', 'soft_bounced', 'hard_bounce'], true)) {
            $updates['bounce_reason'] = $reason;
        }

        $delivery->forceFill($updates)->save();
    }

    private function applyToContact(
        AudienceContact $contact,
        string $eventType,
        CarbonImmutable $eventDate,
        array $payload,
    ): void {
        if ($eventType === 'hard_bounce') {
            $contact->forceFill([
                'hard_bounced_at' => $contact->hard_bounced_at ?? $eventDate,
                'last_bounce_reason' => $this->reason($payload),
            ])->save();

            return;
        }

        if (in_array($eventType, ['unsubscribe', 'unsubscribed'], true)) {
            $contact->forceFill([
                'accepts_email' => false,
                'unsubscribed_at' => $contact->unsubscribed_at ?? $eventDate,
            ])->save();
        }
    }

    private function statusForEvent(string $eventType): ?string
    {
        return match ($eventType) {
            'delivered' => SegmentMessageDelivery::STATUS_DELIVERED,
            'opened', 'proxy_open' => SegmentMessageDelivery::STATUS_OPENED,
            'click' => SegmentMessageDelivery::STATUS_CLICKED,
            'soft_bounce', 'soft_bounced' => SegmentMessageDelivery::STATUS_SOFT_BOUNCED,
            'hard_bounce' => SegmentMessageDelivery::STATUS_HARD_BOUNCED,
            'unsubscribe', 'unsubscribed' => SegmentMessageDelivery::STATUS_UNSUBSCRIBED,
            'spam' => SegmentMessageDelivery::STATUS_COMPLAINED,
            'blocked' => SegmentMessageDelivery::STATUS_BLOCKED,
            'error' => SegmentMessageDelivery::STATUS_ERROR,
            default => null,
        };
    }

    /**
     * @return list<string>
     */
    private function timestampColumns(string $eventType): array
    {
        return match ($eventType) {
            'delivered' => ['delivered_at'],
            'opened', 'proxy_open' => ['opened_at'],
            'click' => ['clicked_at'],
            'soft_bounce', 'soft_bounced' => ['soft_bounced_at'],
            'hard_bounce' => ['hard_bounced_at'],
            'unsubscribe', 'unsubscribed' => ['unsubscribed_at'],
            'spam' => ['complained_at'],
            default => [],
        };
    }

    private function shouldApplyStatus(?string $currentStatus, string $newStatus): bool
    {
        return $this->statusRank($newStatus) >= $this->statusRank($currentStatus);
    }

    private function statusRank(?string $status): int
    {
        return match ($status) {
            SegmentMessageDelivery::STATUS_PENDING => 10,
            SegmentMessageDelivery::STATUS_SYNCED_TO_BREVO => 20,
            SegmentMessageDelivery::STATUS_SENDING => 30,
            SegmentMessageDelivery::STATUS_SENT_TO_PROVIDER => 40,
            SegmentMessageDelivery::STATUS_SENT => 50,
            SegmentMessageDelivery::STATUS_DELIVERED => 60,
            SegmentMessageDelivery::STATUS_OPENED => 70,
            SegmentMessageDelivery::STATUS_CLICKED => 80,
            SegmentMessageDelivery::STATUS_SOFT_BOUNCED => 90,
            SegmentMessageDelivery::STATUS_HARD_BOUNCED,
            SegmentMessageDelivery::STATUS_UNSUBSCRIBED,
            SegmentMessageDelivery::STATUS_COMPLAINED,
            SegmentMessageDelivery::STATUS_BLOCKED,
            SegmentMessageDelivery::STATUS_ERROR,
            SegmentMessageDelivery::STATUS_FAILED => 100,
            default => 0,
        };
    }

    private function rawEventId(array $payload): ?string
    {
        $value = Arr::get($payload, 'id') ?? Arr::get($payload, 'message-id') ?? Arr::get($payload, 'messageId');

        return is_scalar($value) ? (string) $value : null;
    }

    private function reason(array $payload): ?string
    {
        $reason = $payload['reason'] ?? null;

        return is_scalar($reason) && (string) $reason !== '' ? Str::limit((string) $reason, 1000) : null;
    }
}
