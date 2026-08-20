<?php

namespace App\Modules\Contacts\Filament\Resources\Contacts;

use App\Modules\Contacts\Filament\Resources\Contacts\Pages\ManageContacts;
use App\Modules\Contacts\Models\Contact;
use App\Support\Modules;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
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

    public static function getNavigationLabel(): string { return __('admin.contacts.navigation'); }
    public static function getNavigationGroup(): ?string { return __('admin.contacts.group'); }
    public static function getModelLabel(): string { return __('admin.contacts.singular'); }
    public static function getPluralModelLabel(): string { return __('admin.contacts.plural'); }

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
            Section::make(__('admin.contacts.identity'))
                ->schema([
                    TextInput::make('first_name')->label(__('admin.contacts.first_name'))->maxLength(255),
                    TextInput::make('last_name')->label(__('admin.contacts.last_name'))->maxLength(255),
                    TextInput::make('display_name')
                        ->label(__('admin.contacts.display_name'))
                        ->maxLength(255)
                        ->columnSpanFull(),
                    TextInput::make('organization_name')
                        ->label(__('admin.contacts.organization'))
                        ->maxLength(255)
                        ->columnSpanFull(),
                    TextInput::make('email')->label(__('admin.contacts.email'))->email()->maxLength(255),
                    TextInput::make('phone')->label(__('admin.contacts.phone'))->tel()->maxLength(255),
                    TextInput::make('locale')->label(__('admin.contacts.language'))->maxLength(16),
                    TextInput::make('country_code')->label(__('admin.contacts.country'))->length(2),
                ])
                ->columns(['default' => 1, 'md' => 2]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('display_name')
                    ->label(__('admin.conversations.contact'))
                    ->default(fn (Contact $record): string => trim("{$record->first_name} {$record->last_name}") ?: __('admin.contacts.unnamed'))
                    ->searchable(['display_name', 'first_name', 'last_name']),
                TextColumn::make('email')->label(__('admin.contacts.email'))->searchable(),
                TextColumn::make('phone')->label(__('admin.contacts.phone'))->searchable(),
                TextColumn::make('organization_name')->label(__('admin.contacts.organization'))->toggleable(),
                TextColumn::make('source')->label(__('admin.contacts.source'))->badge(),
                TextColumn::make('updated_at')->label(__('admin.contacts.updated_at'))->dateTime()->sortable(),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Editar')
                    ->modalWidth(Width::FourExtraLarge),
                DeleteAction::make()
                    ->label('Excluir')
                    ->modalHeading('Excluir contato?')
                    ->modalDescription('O contato será excluído permanentemente. As solicitações, conversas e inscrições relacionadas serão mantidas, mas desvinculadas deste contato.')
                    ->modalSubmitActionLabel('Excluir contato'),
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
