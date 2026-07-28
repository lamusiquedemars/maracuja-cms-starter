<?php

namespace App\Modules\Conversations\Events;

use App\Modules\Conversations\Models\Conversation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversationStarted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Conversation $conversation,
    ) {}
}
