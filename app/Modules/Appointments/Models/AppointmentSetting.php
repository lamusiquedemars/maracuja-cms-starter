<?php

namespace App\Modules\Appointments\Models;

use App\Modules\Appointments\Enums\AppointmentMode;
use App\Modules\Appointments\Enums\AppointmentProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class AppointmentSetting extends Model
{
    protected $fillable = [
        'is_enabled',
        'provider',
        'mode',
        'booking_url',
        'timezone',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'provider' => AppointmentProvider::class,
            'mode' => AppointmentMode::class,
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $setting): void {
            if ($setting->is_enabled && blank($setting->booking_url)) {
                throw ValidationException::withMessages([
                    'booking_url' => 'Renseignez le lien de la page de réservation.',
                ]);
            }
        });
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'is_enabled' => false,
            'provider' => AppointmentProvider::Fake,
            'mode' => AppointmentMode::AfterReview,
            'booking_url' => null,
            'timezone' => (string) config('app.timezone', 'UTC'),
        ]);
    }

    public function canBookDirectly(): bool
    {
        return $this->is_enabled
            && $this->mode === AppointmentMode::Direct
            && filled($this->booking_url);
    }
}
