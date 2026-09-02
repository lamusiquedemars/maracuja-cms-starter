<?php

namespace App\Modules\Acquisition\Filament\Resources\AcquisitionSettings\Pages;

use App\Modules\Acquisition\Filament\Resources\AcquisitionSettings\AcquisitionSettingResource;
use App\Modules\Acquisition\Models\AcquisitionSetting;
use Filament\Resources\Pages\ManageRecords;

class ManageAcquisitionSettings extends ManageRecords
{
    protected static string $resource = AcquisitionSettingResource::class;

    public function mount(): void
    {
        AcquisitionSetting::current();

        parent::mount();
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
