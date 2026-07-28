<?php

namespace App\Modules\Conversations\Enums;

enum ConversationChannel: string
{
    case Website = 'website';
    case WhatsApp = 'whatsapp';
    case Email = 'email';
    case Instagram = 'instagram';
}
