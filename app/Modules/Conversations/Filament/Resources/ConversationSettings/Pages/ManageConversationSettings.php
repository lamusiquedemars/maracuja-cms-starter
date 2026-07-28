<?php

namespace App\Modules\Conversations\Filament\Resources\ConversationSettings\Pages;

use App\Modules\Conversations\Filament\Resources\ConversationSettings\ConversationSettingResource;
use App\Modules\Conversations\Models\ConversationSetting;
use Filament\Resources\Pages\ManageRecords;

class ManageConversationSettings extends ManageRecords
{
    protected static string $resource = ConversationSettingResource::class;

    public function mount(): void
    {
        ConversationSetting::current();

        parent::mount();
    }
}
