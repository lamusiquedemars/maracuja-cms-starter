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
            if (($data['role'] ?? null) === User::ROLE_SITE_MANAGER && ($data['content_permissions'] ?? null) === null) {
                $data['content_permissions'] = array_keys(collect(ContentPermissions::labels())->filter(fn (string $label, string $module): bool => Modules::enabled($module))->all());
            }
            return $data;
        })];
    }
}
