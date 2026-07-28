<?php

namespace App\Modules\Conversations\Contracts;

use App\Modules\Conversations\Data\AiConversationRequest;
use App\Modules\Conversations\Data\AiConversationResult;

interface ConversationAiProvider
{
    public function respond(AiConversationRequest $request): AiConversationResult;
}
