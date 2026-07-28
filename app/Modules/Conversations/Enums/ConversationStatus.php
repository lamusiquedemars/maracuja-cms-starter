<?php

namespace App\Modules\Conversations\Enums;

enum ConversationStatus: string
{
    case New = 'new';
    case AiActive = 'ai_active';
    case WaitingForVisitor = 'waiting_for_visitor';
    case NeedsHuman = 'needs_human';
    case HumanActive = 'human_active';
    case Closed = 'closed';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Nouvelle',
            self::AiActive => 'IA active',
            self::WaitingForVisitor => 'En attente du visiteur',
            self::NeedsHuman => 'À traiter',
            self::HumanActive => 'Prise en charge',
            self::Closed => 'Clôturée',
            self::Archived => 'Archivée',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::New, self::NeedsHuman => 'danger',
            self::AiActive => 'info',
            self::WaitingForVisitor => 'warning',
            self::HumanActive => 'success',
            self::Closed, self::Archived => 'gray',
        };
    }
}
