<?php

namespace App\Modules\Appointments\Enums;

enum AppointmentMode: string
{
    case AfterReview = 'after_review';
    case Direct = 'direct';

    public function label(): string
    {
        return match ($this) {
            self::AfterReview => 'Après validation de la demande',
            self::Direct => 'Réservation directe par le visiteur',
        };
    }
}
