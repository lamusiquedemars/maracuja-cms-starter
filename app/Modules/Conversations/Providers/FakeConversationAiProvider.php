<?php

namespace App\Modules\Conversations\Providers;

use App\Modules\Conversations\Contracts\ConversationAiProvider;
use App\Modules\Conversations\Data\AiConversationRequest;
use App\Modules\Conversations\Data\AiConversationResult;
use App\Modules\Conversations\Enums\ConversationUrgency;

class FakeConversationAiProvider implements ConversationAiProvider
{
    public function respond(AiConversationRequest $request): AiConversationResult
    {
        return new AiConversationResult(
            reply: 'Merci pour votre message. Pouvez-vous préciser brièvement votre besoin ?',
            summary: 'Nouvelle demande en cours de qualification.',
            topic: null,
            urgency: ConversationUrgency::Unknown,
            requiresHuman: false,
            handoverReason: null,
            offerContactOptions: false,
        );
    }
}
