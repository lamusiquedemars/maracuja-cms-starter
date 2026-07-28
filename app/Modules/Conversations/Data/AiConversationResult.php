<?php

namespace App\Modules\Conversations\Data;

use App\Modules\Conversations\Enums\ConversationUrgency;

readonly class AiConversationResult
{
    /**
     * @param  array<string, string|bool|null>  $qualification
     */
    public function __construct(
        public string $reply,
        public string $summary,
        public ?string $topic,
        public ConversationUrgency $urgency,
        public bool $requiresHuman,
        public array $qualification = [],
    ) {}
}
