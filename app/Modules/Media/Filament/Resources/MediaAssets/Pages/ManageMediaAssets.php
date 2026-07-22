<?php

namespace App\Modules\Media\Filament\Resources\MediaAssets\Pages;

use App\Modules\Media\Filament\Actions\MediaUploadAction;
use App\Modules\Media\Filament\Resources\MediaAssets\MediaAssetResource;
use Filament\Resources\Pages\ManageRecords;

class ManageMediaAssets extends ManageRecords
{
    protected static string $resource = MediaAssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            MediaUploadAction::make(),
        ];
    }
}
