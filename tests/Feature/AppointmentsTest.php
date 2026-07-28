<?php

namespace Tests\Feature;

use App\Modules\Appointments\Enums\AppointmentMode;
use App\Modules\Appointments\Enums\AppointmentProvider;
use App\Modules\Appointments\Enums\AppointmentStatus;
use App\Modules\Appointments\Models\AppointmentSetting;
use App\Modules\Inquiries\Models\Inquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AppointmentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_appointments_are_disabled_and_provider_neutral_by_default(): void
    {
        $setting = AppointmentSetting::current();

        $this->assertFalse($setting->is_enabled);
        $this->assertSame(AppointmentProvider::Fake, $setting->provider);
        $this->assertSame(AppointmentMode::AfterReview, $setting->mode);
        $this->assertSame(config('app.timezone'), $setting->timezone);
    }

    public function test_an_enabled_booking_page_requires_a_url(): void
    {
        $this->expectException(ValidationException::class);

        AppointmentSetting::query()->create([
            'is_enabled' => true,
            'provider' => AppointmentProvider::Brevo,
            'mode' => AppointmentMode::Direct,
            'booking_url' => null,
            'timezone' => 'Europe/Paris',
        ]);
    }

    public function test_an_inquiry_can_track_an_external_appointment_without_owning_the_calendar(): void
    {
        $inquiry = Inquiry::query()->create([
            'name' => 'Camille Martin',
            'email' => 'camille@example.test',
            'message' => 'Je souhaite être rappelé.',
            'appointment_status' => AppointmentStatus::Booked,
            'scheduled_start_at' => '2026-08-03 12:00:00',
            'appointment_timezone' => 'Europe/Paris',
            'appointment_external_reference' => 'meeting-42',
        ]);

        $this->assertSame(AppointmentStatus::Booked, $inquiry->appointment_status);
        $this->assertSame('meeting-42', $inquiry->appointment_external_reference);
        $this->assertNotNull($inquiry->scheduled_start_at);
    }
}
