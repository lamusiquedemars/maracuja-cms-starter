<?php

namespace App\Modules\Appointments\Enums;

enum AppointmentStatus: string
{
    case NotRequested = 'not_requested';
    case Requested = 'requested';
    case BookingOpened = 'booking_opened';
    case Booked = 'booked';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::NotRequested => 'Non demandé',
            self::Requested => 'Demandé',
            self::BookingOpened => 'Réservation proposée',
            self::Booked => 'Réservé',
            self::Cancelled => 'Annulé',
        };
    }
}
