<?php

namespace App\Modules\Appointments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Appointments\Enums\AppointmentProvider;
use App\Modules\Appointments\Models\AppointmentSetting;
use App\Modules\SiteSettings\Models\SiteSetting;
use App\Support\Modules;
use Illuminate\Contracts\View\View;

class AppointmentController extends Controller
{
    public function __invoke(): View
    {
        abort_unless(Modules::enabled('appointments'), 404);

        $appointment = AppointmentSetting::current();

        abort_unless($appointment->canBookDirectly(), 404);

        return view('site.appointment', [
            'settings' => SiteSetting::current(),
            'appointment' => $appointment,
            'isDemo' => $appointment->provider === AppointmentProvider::Fake,
        ]);
    }
}
