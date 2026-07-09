<?php

namespace App\Modules\Audience\Exceptions;

use RuntimeException;

class BrevoAudienceException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly ?string $technicalMessage = null,
    ) {
        parent::__construct($message);
    }

    public function technicalMessage(): string
    {
        return $this->technicalMessage ?? $this->getMessage();
    }
}
