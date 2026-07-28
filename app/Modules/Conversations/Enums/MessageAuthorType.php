<?php

namespace App\Modules\Conversations\Enums;

enum MessageAuthorType: string
{
    case Visitor = 'visitor';
    case Ai = 'ai';
    case Human = 'human';
    case System = 'system';
}
