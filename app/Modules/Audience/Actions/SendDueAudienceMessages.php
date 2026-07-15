<?php

namespace App\Modules\Audience\Actions;

use App\Modules\Audience\Models\SegmentMessage;
use Throwable;

class SendDueAudienceMessages
{
    /**
     * @return array{sent: int, failed: int, skipped: int, processed: int, brevo_sent: int, brevo_failed: int}
     */
    public static function run(int $limit = 25, int $maxSeconds = 180, int $maxAttempts = 3): array
    {
        $limit = max(1, $limit);
        $startedAt = microtime(true);

        $stats = [
            'sent' => 0,
            'failed' => 0,
            'skipped' => 0,
            'processed' => 0,
            'brevo_sent' => 0,
            'brevo_failed' => 0,
        ];

        SegmentMessage::query()
            ->where('provider', SegmentMessage::PROVIDER_BREVO)
            ->where('status', SegmentMessage::STATUS_QUEUED)
            ->where(function ($query): void {
                $query
                    ->whereNull('scheduled_at')
                    ->orWhere('scheduled_at', '<=', now());
            })
            ->orderBy('scheduled_at')
            ->limit($limit)
            ->get()
            ->each(function (SegmentMessage $message) use (&$stats, $maxSeconds, $startedAt): void {
                if ((microtime(true) - $startedAt) >= $maxSeconds) {
                    return;
                }

                try {
                    DispatchSegmentMessage::run($message);
                    $stats['brevo_sent']++;
                } catch (Throwable) {
                    $stats['brevo_failed']++;
                }

                $stats['processed']++;
            });

        if ((microtime(true) - $startedAt) >= $maxSeconds) {
            return $stats;
        }

        $standardStats = SendPendingSegmentMessages::run(
            limit: $limit,
            maxSeconds: $maxSeconds,
            maxAttempts: $maxAttempts,
        );

        $stats['sent'] += $standardStats['sent'];
        $stats['failed'] += $standardStats['failed'];
        $stats['skipped'] += $standardStats['skipped'];
        $stats['processed'] += $standardStats['processed'];

        return $stats;
    }
}
