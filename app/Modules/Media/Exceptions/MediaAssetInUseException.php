<?php

namespace App\Modules\Media\Exceptions;

use LogicException;

class MediaAssetInUseException extends LogicException
{
    public static function forMedia(string $name): self
    {
        return new self("Le média « {$name} » est encore utilisé et ne peut pas être supprimé.");
    }
}
