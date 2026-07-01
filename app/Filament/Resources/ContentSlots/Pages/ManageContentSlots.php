<?php

namespace App\Filament\Resources\ContentSlots\Pages;

use App\Filament\Resources\ContentSlots\ContentSlotResource;
use Filament\Resources\Pages\ManageRecords;

class ManageContentSlots extends ManageRecords
{
    protected static string $resource = ContentSlotResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
