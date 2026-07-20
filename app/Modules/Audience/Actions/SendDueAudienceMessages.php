<?php

namespace App\Modules\Audience\Actions;

class SendDueAudienceMessages
{
    /**
     * @return array{sent: int, failed: int, skipped: int, processed: int}
     */
    public static function run(int $limit = 25, int $maxSeconds = 180, int $maxAttempts = 3): array
    {
        return SendPendingSegmentMessages::run(
            limit: $limit,
            maxSeconds: $maxSeconds,
            maxAttempts: $maxAttempts,
        );
    }
}
