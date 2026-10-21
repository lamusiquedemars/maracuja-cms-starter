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
use Filament\Forms\Components\Toggle;
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
                    User::ROLE_ADMIN => 'Administrateur : tous les modules actifs, réglages et SEO.',
                    User::ROLE_SITE_MANAGER => 'Responsable : tous les modules éditoriaux actifs, sans réglages ni SEO.',
                    User::ROLE_EDITOR => 'Éditeur : droits historiques larges jusqu’à sa première personnalisation.',
                    default => 'Lecteur : consultation interne uniquement.',
                };
            }),
            CheckboxList::make('inherited_module_preview')->label('Modules effectivement accordés par le profil')
                ->options(self::activeModuleOptions())->disabled()->dehydrated(false)
                ->visible(fn (?User $record): bool => $record?->content_permissions === null)
                ->afterStateHydrated(function (CheckboxList $component, ?User $record): void {
                    $component->state(self::inheritedModuleKeys($record));
                }),
            Toggle::make('customize_permissions')->label('Personnaliser les modules autorisés')
                ->default(fn (?User $record): bool => $record?->content_permissions !== null)
                ->live()->dehydrated(false)
                ->helperText('Active cette option pour remplacer les droits hérités par une liste précise.'),
            CheckboxList::make('content_permissions')->label('Modules autorisés')->options(
                self::activeModuleOptions()
            )->columns(2)->visible(fn ($get): bool => (bool) $get('customize_permissions'))
                ->helperText('Ces cases remplacent le profil. Une liste vide retire tous les modules.'),
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

    /** @return array<string, string> */
    public static function activeModuleOptions(): array
    {
        return collect(ContentPermissions::labels())
            ->filter(fn (string $label, string $module): bool => Modules::enabled($module))
            ->all();
    }

    /** @return list<string> */
    public static function inheritedModuleKeys(?User $user): array
    {
        if (! $user || ! in_array($user->role, [User::ROLE_ADMIN, User::ROLE_SITE_MANAGER, User::ROLE_EDITOR], true)) {
            return [];
        }

        return array_keys(self::activeModuleOptions());
    }
}
