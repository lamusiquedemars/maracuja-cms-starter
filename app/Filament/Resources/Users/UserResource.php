<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use App\Support\ContentPermissions;
use App\Support\Modules;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationLabel = 'Utilisateurs et accès';
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;
    protected static string|\UnitEnum|null $navigationGroup = 'Réglages';

    public static function canAccess(): bool { return auth()->user() instanceof User && auth()->user()->isAdministrator(); }
    public static function canViewAny(): bool { return static::canAccess(); }
    public static function canCreate(): bool { return static::canAccess(); }
    public static function canEdit($record): bool { return static::canAccess(); }
    public static function canDelete($record): bool { return static::canAccess() && $record->id !== auth()->id(); }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nom')->required()->maxLength(255),
            TextInput::make('email')->label('E-mail')->email()->required()->unique(ignoreRecord: true),
            TextInput::make('password')->label('Mot de passe')->password()->minLength(12)->dehydrated(fn ($state): bool => filled($state))->helperText('Laisser vide pour conserver le mot de passe actuel.'),
            Select::make('role')->label('Base de droits')->options([
                User::ROLE_ADMIN => 'Administrateur Maracuja',
                User::ROLE_SITE_MANAGER => 'Responsable du site',
                User::ROLE_EDITOR => 'Éditeur ciblé',
                User::ROLE_VIEWER => 'Lecteur',
            ])->required()->live()->helperText('Base de départ ; les modules cochés ci-dessous deviennent une exception explicite.'),
            Placeholder::make('profile_permissions')->label('Droits hérités du profil')->content(function (?User $record): string {
                if ($record?->content_permissions !== null) return 'Cet utilisateur utilise une personnalisation explicite des modules.';
                return match ($record?->role) {
                    User::ROLE_ADMIN => 'Administrateur : tous les modules actifs, réglages globaux et SEO.',
                    User::ROLE_SITE_MANAGER => 'Responsable : tous les modules éditoriaux actifs, sans réglages globaux ni SEO.',
                    User::ROLE_EDITOR => 'Éditeur : droits hérités pendant la transition ; cochez des modules pour le restreindre explicitement.',
                    default => 'Lecteur : consultation interne uniquement.',
                };
            }),
            CheckboxList::make('content_permissions')->label('Modules autorisés')->options(
                collect(ContentPermissions::labels())->filter(fn (string $label, string $module): bool => Modules::enabled($module))->all()
            )->columns(2)->helperText('Ces cases personnalisent le profil. Laisser la liste vide n’est pas la même chose que ne rien personnaliser : une liste enregistrée vide retire tous les modules.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('Nom')->searchable(),
            TextColumn::make('email')->label('E-mail')->searchable(),
            TextColumn::make('role')->label('Base')->badge(),
            TextColumn::make('content_permissions')->label('Modules')->formatStateUsing(fn (?array $state): string => $state === null ? 'Selon le profil' : (string) count($state).' module(s)'),
        ])->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array { return ['index' => ManageUsers::route('/')]; }
}
