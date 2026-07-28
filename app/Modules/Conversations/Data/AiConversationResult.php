<?php

namespace App\Modules\Conversations\Data;

use App\Modules\Conversations\Enums\ConversationUrgency;
use App\Modules\Conversations\Enums\HandoverReason;

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
        public ?HandoverReason $handoverReason,
        public bool $offerContactOptions,
        public array $qualification = [],
    ) {}
}
