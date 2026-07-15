<?php

namespace App\Modules\Audience\Actions;

use App\Modules\Audience\Models\SegmentMessage;

class SendSegmentMessage
{
    public static function run(SegmentMessage $message): int
    {
        $stats = DispatchSegmentMessage::run($message);

        return max($stats['queued'], $stats['sent']);
    }
}
