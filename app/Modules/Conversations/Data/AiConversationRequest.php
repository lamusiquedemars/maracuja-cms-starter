<?php

namespace App\Modules\Conversations\Data;

readonly class AiConversationRequest
{
    /**
     * @param  list<array{role: string, content: string}>  $messages
     */
    public function __construct(
        public string $instructions,
        public array $messages,
        public ?string $safetyIdentifier = null,
    ) {}
}
