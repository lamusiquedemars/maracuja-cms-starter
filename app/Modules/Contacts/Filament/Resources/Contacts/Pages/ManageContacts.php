<?php

namespace App\Modules\Contacts\Filament\Resources\Contacts\Pages;

use App\Modules\Contacts\Filament\Resources\Contacts\ContactResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageContacts extends ManageRecords
{
    protected static string $resource = ContactResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Créer un contact'),
        ];
    }
}
