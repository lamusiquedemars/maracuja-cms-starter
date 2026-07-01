<?php

namespace App\Filament\Resources\Venues;

use App\Filament\Resources\Venues\Pages\ManageVenues;
use App\Modules\Venues\Models\Venue;
use App\Support\Modules;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class VenueResource extends Resource
{
    protected static ?string $model = Venue::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?string $navigationLabel = 'Lieux';

    protected static UnitEnum|string|null $navigationGroup = 'Organisation';

    protected static ?string $modelLabel = 'lieu';

    protected static ?string $pluralModelLabel = 'lieux';

    protected static ?int $navigationSort = 40;

    public static function shouldRegisterNavigation(): bool
    {
        return Modules::enabled('venues');
    }

    public static function canAccess(): bool
    {
        return Modules::enabled('venues') && parent::canAccess();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identité')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nom')
                            ->required(),
                        TextInput::make('slug')
                            ->label('Slug')
                            ->helperText('Laisser vide pour le générer automatiquement.')
                            ->unique(ignoreRecord: true),
                        Select::make('type')
                            ->label('Type')
                            ->options([
                                'venue' => 'Salle',
                                'festival' => 'Festival',
                                'theater' => 'Théâtre',
                                'gallery' => 'Galerie',
                                'school' => 'École / formation',
                                'church' => 'Église / lieu patrimonial',
                                'market' => 'Marché / salon',
                                'other' => 'Autre',
                            ])
                            ->searchable(),
                        TextInput::make('capacity')
                            ->label('Capacité')
                            ->numeric()
                            ->minValue(0),
                        Toggle::make('is_active')
                            ->label('Actif')
                            ->required()
                            ->default(true),
                    ]),
                Section::make('Adresse publique')
                    ->columns(2)
                    ->schema([
                        TextInput::make('address')
                            ->label('Adresse')
                            ->columnSpanFull(),
                        TextInput::make('postal_code')
                            ->label('Code postal'),
                        TextInput::make('city')
                            ->label('Ville'),
                        TextInput::make('region')
                            ->label('Région'),
                        TextInput::make('country')
                            ->label('Pays'),
                        TextInput::make('maps_url')
                            ->label('Lien carte')
                            ->url()
                            ->columnSpanFull(),
                        TextInput::make('website_url')
                            ->label('Site web')
                            ->url(),
                        TextInput::make('public_email')
                            ->label('Email public')
                            ->email(),
                        TextInput::make('public_phone')
                            ->label('Téléphone public'),
                    ]),
                Section::make('Contact interne')
                    ->columns(3)
                    ->schema([
                        TextInput::make('contact_name')
                            ->label('Nom'),
                        TextInput::make('contact_email')
                            ->label('Email')
                            ->email(),
                        TextInput::make('contact_phone')
                            ->label('Téléphone'),
                    ]),
                Section::make('Notes')
                    ->schema([
                        Textarea::make('accessibility_notes')
                            ->label('Accessibilité')
                            ->columnSpanFull(),
                        Textarea::make('technical_notes')
                            ->label('Technique / logistique')
                            ->columnSpanFull(),
                        Textarea::make('private_notes')
                            ->label('Notes privées')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Type')
                    ->badge(),
                TextColumn::make('city')
                    ->label('Ville')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('country')
                    ->label('Pays')
                    ->searchable(),
                TextColumn::make('events_count')
                    ->counts('events')
                    ->label('Événements')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageVenues::route('/'),
        ];
    }
}
