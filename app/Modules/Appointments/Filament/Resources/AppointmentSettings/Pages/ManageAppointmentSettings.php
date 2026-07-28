<?php

namespace App\Modules\Appointments\Filament\Resources\AppointmentSettings\Pages;

use App\Modules\Appointments\Filament\Resources\AppointmentSettings\AppointmentSettingResource;
use App\Modules\Appointments\Models\AppointmentSetting;
use Filament\Resources\Pages\ManageRecords;

class ManageAppointmentSettings extends ManageRecords
{
    protected static string $resource = AppointmentSettingResource::class;

    public function mount(): void
    {
        AppointmentSetting::current();

        parent::mount();
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
