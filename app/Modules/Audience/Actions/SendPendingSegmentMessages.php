<?php

namespace App\Modules\Audience\Actions;

use App\Modules\Audience\Mail\SegmentMessageMail;
use App\Modules\Audience\Models\SegmentMessage;
use App\Modules\Audience\Models\SegmentMessageDelivery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendPendingSegmentMessages
{
    /**
     * @return array{sent: int, failed: int, skipped: int, processed: int}
     */
    public static function run(int $limit = 16, int $maxSeconds = 180, int $maxAttempts = 3, ?SegmentMessage $message = null): array
    {
        $limit = max(1, $limit);
        $maxAttempts = max(1, $maxAttempts);
        $startedAt = microtime(true);

        $stats = [
            'sent' => 0,
            'failed' => 0,
            'skipped' => 0,
            'processed' => 0,
        ];

        while ($stats['processed'] < $limit && (microtime(true) - $startedAt) < $maxSeconds) {
            $delivery = self::claimNextDelivery($maxAttempts, $message);

            if (! $delivery) {
                break;
            }

            $result = self::sendDelivery($delivery);
            $stats[$result]++;
            $stats['processed']++;
        }

        self::refreshMessageStatuses($maxAttempts);

        return $stats;
    }

    public static function runForMessage(SegmentMessage $message, int $limit = 16, int $maxSeconds = 180, int $maxAttempts = 3): array
    {
        return self::run(
            limit: $limit,
            maxSeconds: $maxSeconds,
            maxAttempts: $maxAttempts,
            message: $message,
        );
    }

    private static function claimNextDelivery(int $maxAttempts, ?SegmentMessage $message = null): ?SegmentMessageDelivery
    {
        $query = SegmentMessageDelivery::query()
            ->whereIn('status', [
                SegmentMessageDelivery::STATUS_PENDING,
                SegmentMessageDelivery::STATUS_FAILED,
            ])
            ->whereHas('segmentMessage', function ($query): void {
                $query
                    ->whereNull('scheduled_at')
                    ->orWhere('scheduled_at', '<=', now());
            })
            ->where('attempts', '<', $maxAttempts);

        if ($message) {
            $query->where('segment_message_id', $message->id);
        }

        $delivery = $query->orderBy('id')->first();

        if (! $delivery) {
            return null;
        }

        $claimQuery = SegmentMessageDelivery::query()
            ->whereKey($delivery->id)
            ->whereIn('status', [
                SegmentMessageDelivery::STATUS_PENDING,
                SegmentMessageDelivery::STATUS_FAILED,
            ])
            ->whereHas('segmentMessage', function ($query): void {
                $query
                    ->whereNull('scheduled_at')
                    ->orWhere('scheduled_at', '<=', now());
            })
            ->where('attempts', '<', $maxAttempts);

        if ($message) {
            $claimQuery->where('segment_message_id', $message->id);
        }

        $claimed = $claimQuery->update([
                'status' => SegmentMessageDelivery::STATUS_SENDING,
                'attempts' => DB::raw('attempts + 1'),
                'attempted_at' => now(),
                'updated_at' => now(),
            ]);

        if ($claimed !== 1) {
            return null;
        }

        return $delivery->refresh();
    }

    private static function sendDelivery(SegmentMessageDelivery $delivery): string
    {
        $message = $delivery->segmentMessage;
        $contact = $delivery->contact;

        if (! $message || ! $contact || ! $contact->canReceiveSegmentEmail()) {
            $delivery->forceFill([
                'status' => SegmentMessageDelivery::STATUS_SKIPPED,
                'error_message' => 'Contact absent, désinscrit ou non éligible.',
            ])->save();

            return 'skipped';
        }

        $message->forceFill([
            'status' => SegmentMessage::STATUS_SENDING,
        ])->save();

        try {
            Mail::to($delivery->email)->send(new SegmentMessageMail($message, $contact));

            $delivery->forceFill([
                'status' => SegmentMessageDelivery::STATUS_SENT,
                'error_message' => null,
                'sent_at' => now(),
            ])->save();

            $contact->forceFill([
                'last_contacted_at' => now(),
            ])->save();

            return 'sent';
        } catch (Throwable $exception) {
            $delivery->forceFill([
                'status' => SegmentMessageDelivery::STATUS_FAILED,
                'error_message' => $exception->getMessage(),
            ])->save();

            return 'failed';
        }
    }

    private static function refreshMessageStatuses(int $maxAttempts): void
    {
        SegmentMessage::query()
            ->whereIn('status', [SegmentMessage::STATUS_QUEUED, SegmentMessage::STATUS_SENDING])
            ->get()
            ->each(function (SegmentMessage $message) use ($maxAttempts): void {
                $remaining = $message->deliveries()
                    ->where(function ($query) use ($maxAttempts): void {
                        $query
                            ->whereIn('status', [
                                SegmentMessageDelivery::STATUS_PENDING,
                                SegmentMessageDelivery::STATUS_SENDING,
                            ])
                            ->orWhere(function ($query) use ($maxAttempts): void {
                                $query
                                    ->where('status', SegmentMessageDelivery::STATUS_FAILED)
                                    ->where('attempts', '<', $maxAttempts);
                            });
                    })
                    ->exists();

                $sentCount = $message->deliveries()
                    ->where('status', SegmentMessageDelivery::STATUS_SENT)
                    ->count();

                if ($remaining) {
                    $message->forceFill([
                        'status' => $message->scheduled_at?->isFuture()
                            ? SegmentMessage::STATUS_QUEUED
                            : SegmentMessage::STATUS_SENDING,
                        'recipients_count' => $sentCount,
                    ])->save();

                    return;
                }

                $message->forceFill([
                    'status' => SegmentMessage::STATUS_SENT,
                    'recipients_count' => $sentCount,
                    'sent_at' => now(),
                ])->save();
            });
    }
}
