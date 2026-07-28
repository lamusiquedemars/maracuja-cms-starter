<?php

namespace App\Modules\Conversations\Enums;

enum MessageVisibility: string
{
    case Public = 'public';
    case Internal = 'internal';
}
