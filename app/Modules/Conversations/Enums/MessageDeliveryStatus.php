<?php

namespace App\Modules\Conversations\Enums;

enum MessageDeliveryStatus: string
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Failed = 'failed';
}
