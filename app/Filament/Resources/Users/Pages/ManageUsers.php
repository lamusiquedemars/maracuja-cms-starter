<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Support\ContentPermissions;
use App\Support\Modules;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

final class ManageUsers extends ManageRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Ajouter un utilisateur')->mutateDataUsing(function (array $data): array {
            if (! ($data['customize_permissions'] ?? false)) {
                $data['content_permissions'] = null;
            }
            unset($data['customize_permissions']);
            return $data;
        })];
    }
}
