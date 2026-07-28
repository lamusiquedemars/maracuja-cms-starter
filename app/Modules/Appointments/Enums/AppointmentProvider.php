<?php

namespace App\Modules\Appointments\Enums;

enum AppointmentProvider: string
{
    case Fake = 'fake';
    case Brevo = 'brevo';

    public function label(): string
    {
        return match ($this) {
            self::Fake => 'Démonstration',
            self::Brevo => 'Brevo Meetings',
        };
    }
}
