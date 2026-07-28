<?php

namespace App\Modules\Conversations\Enums;

enum HandoverReason: string
{
    case VisitorRequest = 'visitor_request';
    case Urgency = 'urgency';
    case Qualified = 'qualified';
    case OutsideScope = 'outside_scope';
    case InteractionLimit = 'interaction_limit';
    case AssistantLimit = 'assistant_limit';
    case TechnicalError = 'technical_error';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::VisitorRequest => 'Le visiteur souhaite parler à l’équipe',
            self::Urgency => 'Urgence potentielle',
            self::Qualified => 'Demande suffisamment qualifiée',
            self::OutsideScope => 'Conversation hors périmètre',
            self::InteractionLimit => 'Limite de conversation atteinte',
            self::AssistantLimit => 'Limite du rôle de l’assistant',
            self::TechnicalError => 'Problème technique de l’assistant',
            self::Manual => 'Transfert manuel',
        };
    }
}
