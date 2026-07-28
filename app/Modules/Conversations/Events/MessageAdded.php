<?php

namespace App\Modules\Conversations\Events;

use App\Modules\Conversations\Models\Message;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageAdded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Message $message,
    ) {}
}
