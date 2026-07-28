<?php

namespace App\Modules\Contacts\Filament\Resources\Contacts;

use App\Modules\Contacts\Filament\Resources\Contacts\Pages\ManageContacts;
use App\Modules\Contacts\Models\Contact;
use App\Support\Modules;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class ContactResource extends Resource
{
    protected static ?string $model = Contact::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static ?string $navigationLabel = 'Contacts';

    protected static UnitEnum|string|null $navigationGroup = 'Relation client';

    protected static ?string $modelLabel = 'contact';

    protected static ?string $pluralModelLabel = 'contacts';

    protected static ?int $navigationSort = 10;

    public static function shouldRegisterNavigation(): bool
    {
        return Modules::enabled('contacts');
    }

    public static function canAccess(): bool
    {
        return Modules::enabled('contacts') && parent::canAccess();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identité et coordonnées')
                ->schema([
                    TextInput::make('first_name')->label('Prénom')->maxLength(255),
                    TextInput::make('last_name')->label('Nom')->maxLength(255),
                    TextInput::make('display_name')->label('Nom affiché')->maxLength(255),
                    TextInput::make('organization_name')->label('Organisation')->maxLength(255),
                    TextInput::make('email')->label('Email')->email()->maxLength(255),
                    TextInput::make('phone')->label('Téléphone')->tel()->maxLength(255),
                    TextInput::make('locale')->label('Langue')->maxLength(16),
                    TextInput::make('country_code')->label('Pays')->length(2),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('display_name')
                    ->label('Contact')
                    ->default(fn (Contact $record): string => trim("{$record->first_name} {$record->last_name}") ?: 'Sans nom')
                    ->searchable(['display_name', 'first_name', 'last_name']),
                TextColumn::make('email')->label('Email')->searchable(),
                TextColumn::make('phone')->label('Téléphone')->searchable(),
                TextColumn::make('organization_name')->label('Organisation')->toggleable(),
                TextColumn::make('source')->label('Origine')->badge(),
                TextColumn::make('updated_at')->label('Mis à jour')->dateTime()->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageContacts::route('/'),
        ];
    }
}
