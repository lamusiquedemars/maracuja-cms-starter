<?php

namespace App\Modules\Audience\Filament\Resources\AudienceBrevoSettings\Pages;

use App\Modules\Audience\Filament\Resources\AudienceBrevoSettings\AudienceBrevoSettingResource;
use App\Modules\Audience\Models\AudienceBrevoSetting;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageAudienceBrevoSettings extends ManageRecords
{
    protected static string $resource = AudienceBrevoSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Configurer Brevo')
                ->visible(fn (): bool => AudienceBrevoSetting::query()->doesntExist()),
        ];
    }
}
