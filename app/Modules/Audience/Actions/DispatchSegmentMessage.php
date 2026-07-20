<?php

namespace App\Modules\Audience\Actions;

use App\Modules\Audience\Models\SegmentMessage;
use App\Modules\Audience\Services\BrevoAudienceService;
use Carbon\CarbonInterface;

class DispatchSegmentMessage
{
    /**
     * @return array{scheduled: bool, queued: int, sent: int, failed: int, skipped: int, processed: int}
     */
    public static function run(SegmentMessage $message, ?CarbonInterface $scheduledAt = null): array
    {
        $message->forceFill([
            'scheduled_at' => $scheduledAt,
        ])->save();

        if ($scheduledAt?->isFuture()) {
            return self::scheduleForLater($message);
        }

        return $message->usesBrevo()
            ? self::sendWithBrevo($message)
            : self::sendStandard($message);
    }

    /**
     * @return array{scheduled: bool, queued: int, sent: int, failed: int, skipped: int, processed: int}
     */
    private static function scheduleForLater(SegmentMessage $message): array
    {
        if ($message->usesBrevo()) {
            app(BrevoAudienceService::class)->scheduleCampaign($message, $message->scheduled_at);

            return [
                'scheduled' => true,
                'queued' => $message->refresh()->recipients_count,
                'sent' => 0,
                'failed' => 0,
                'skipped' => 0,
                'processed' => 1,
            ];
        }

        $queued = QueueSegmentMessage::run($message);

        return [
            'scheduled' => true,
            'queued' => $queued,
            'sent' => 0,
            'failed' => 0,
            'skipped' => 0,
            'processed' => 0,
        ];
    }

    /**
     * @return array{scheduled: bool, queued: int, sent: int, failed: int, skipped: int, processed: int}
     */
    private static function sendStandard(SegmentMessage $message): array
    {
        $queued = QueueSegmentMessage::run($message);
        $stats = SendPendingSegmentMessages::runForMessage($message, limit: 25);

        return [
            'scheduled' => false,
            'queued' => $queued,
            'sent' => $stats['sent'],
            'failed' => $stats['failed'],
            'skipped' => $stats['skipped'],
            'processed' => $stats['processed'],
        ];
    }

    /**
     * @return array{scheduled: bool, queued: int, sent: int, failed: int, skipped: int, processed: int}
     */
    private static function sendWithBrevo(SegmentMessage $message): array
    {
        $service = app(BrevoAudienceService::class);

        if (! $message->brevo_campaign_id) {
            $service->createCampaign($message);
            $message->refresh();
        }

        $service->sendCampaign($message);

        return [
            'scheduled' => false,
            'queued' => $message->refresh()->recipients_count,
            'sent' => $message->recipients_count,
            'failed' => 0,
            'skipped' => 0,
            'processed' => 1,
        ];
    }
}
