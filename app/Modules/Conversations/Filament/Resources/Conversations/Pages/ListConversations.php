<?php

namespace App\Modules\Conversations\Filament\Resources\Conversations\Pages;

use App\Modules\Conversations\Filament\Resources\Conversations\ConversationResource;
use Filament\Resources\Pages\ListRecords;

class ListConversations extends ListRecords
{
    protected static string $resource = ConversationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
