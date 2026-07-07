<?php

namespace App\Modules\Audience\Actions;

use App\Modules\Audience\Models\SegmentMessage;

class SendSegmentMessage
{
    public static function run(SegmentMessage $message): int
    {
        return QueueSegmentMessage::run($message);
    }
}
