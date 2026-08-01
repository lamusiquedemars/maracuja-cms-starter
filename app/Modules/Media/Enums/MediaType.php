<?php

namespace App\Modules\Media\Enums;

enum MediaType: string
{
    case Image = 'image';
    case Document = 'document';
    case Video = 'video';

    public function label(): string
    {
        return match ($this) {
            self::Image => 'Image',
            self::Document => 'Document',
            self::Video => 'Vidéo',
        };
    }
}
