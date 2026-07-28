<?php

namespace App\Modules\Conversations\Data;

use App\Modules\Conversations\Models\Conversation;

readonly class AnonymousConversationSession
{
    public function __construct(
        public Conversation $conversation,
        public string $token,
    ) {}
}
