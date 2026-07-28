<?php

namespace App\Modules\Conversations\Enums;

enum ConversationUrgency: string
{
    case Unknown = 'unknown';
    case Normal = 'normal';
    case High = 'high';
    case Immediate = 'immediate';

    public function label(): string
    {
        return match ($this) {
            self::Unknown => 'À déterminer',
            self::Normal => 'Normale',
            self::High => 'Élevée',
            self::Immediate => 'Immédiate',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Unknown, self::Normal => 'gray',
            self::High => 'warning',
            self::Immediate => 'danger',
        };
    }
}
