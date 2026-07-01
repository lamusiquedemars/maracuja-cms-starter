<?php

namespace App\Filament\Resources\Events;

use App\Filament\Resources\Events\Pages\ManageEvents;
use App\Modules\Events\Models\Event;
use App\Modules\Venues\Models\Venue;
use App\Support\MediaFiles;
use App\Support\Modules;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Événements';

    protected static UnitEnum|string|null $navigationGroup = 'Organisation';

    protected static ?string $modelLabel = 'événement';

    protected static ?string $pluralModelLabel = 'événements';

    protected static ?int $navigationSort = 35;

    public static function shouldRegisterNavigation(): bool
    {
        return Modules::enabled('events');
    }

    public static function canAccess(): bool
    {
        return Modules::enabled('events') && parent::canAccess();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Événement')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->label('Titre')
                            ->required(),
                        TextInput::make('slug')
                            ->label('Slug')
                            ->helperText('Laisser vide pour le générer automatiquement.')
                            ->unique(ignoreRecord: true),
                        Select::make('type')
                            ->label('Type')
                            ->options([
                                'concert' => 'Concert',
                                'workshop' => 'Atelier',
                                'training' => 'Formation',
                                'conference' => 'Conférence',
                                'exhibition' => 'Exposition',
                                'market' => 'Marché / salon',
                                'open_day' => 'Portes ouvertes',
                                'other' => 'Autre',
                            ])
                            ->searchable(),
                        Select::make('venue_id')
                            ->label('Lieu')
                            ->options(fn (): array => Venue::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->preload()
                            ->visible(fn (): bool => Modules::enabled('venues')),
                        Select::make('status')
                            ->label('Statut')
                            ->options([
                                Event::STATUS_SCHEDULED => 'Programmé',
                                Event::STATUS_CANCELLED => 'Annulé',
                                Event::STATUS_POSTPONED => 'Reporté',
                                Event::STATUS_SOLD_OUT => 'Complet',
                            ])
                            ->default(Event::STATUS_SCHEDULED)
                            ->required(),
                        Toggle::make('is_featured')
                            ->label('Mis en avant'),
                    ]),
                Section::make('Dates')
                    ->columns(3)
                    ->schema([
                        DateTimePicker::make('starts_at')
                            ->label('Date et heure de début')
                            ->native(false)
                            ->seconds(false)
                            ->displayFormat('d/m/Y H:i')
                            ->minutesStep(5)
                            ->required(),
                        DateTimePicker::make('ends_at')
                            ->label('Date et heure de fin')
                            ->native(false)
                            ->seconds(false)
                            ->displayFormat('d/m/Y H:i')
                            ->minutesStep(5)
                            ->nullable(),
                        TextInput::make('timezone')
                            ->label('Fuseau horaire')
                            ->placeholder(config('app.timezone')),
                    ]),
                Section::make('Contenu')
                    ->schema([
                        Textarea::make('excerpt')
                            ->label('Résumé')
                            ->columnSpanFull(),
                        RichEditor::make('description')
                            ->label('Description')
                            ->columnSpanFull(),
                        Select::make('existing_image_path')
                            ->label('Choisir une image existante')
                            ->options(fn (): array => MediaFiles::options('events'))
                            ->searchable()
                            ->live()
                            ->dehydrated(false)
                            ->afterStateUpdated(fn (Set $set, ?string $state): mixed => filled($state) ? $set('image_path', $state) : null)
                            ->helperText('Liste les fichiers déjà présents dans storage/app/public/events.'),
                        FileUpload::make('image_path')
                            ->label('Image')
                            ->disk('public')
                            ->directory('events')
                            ->visibility('public')
                            ->fetchFileInformation(false)
                            ->preventFilePathTampering(true, fn (string $file): bool => MediaFiles::isAllowed($file, 'events'))
                            ->image(),
                    ]),
                Section::make('Liens')
                    ->columns(2)
                    ->schema([
                        TextInput::make('ticket_url')
                            ->label('Billetterie / inscription')
                            ->url(),
                        TextInput::make('external_url')
                            ->label('Lien externe')
                            ->url(),
                    ]),
                Section::make('Publication')
                    ->columns(2)
                    ->schema([
                        Toggle::make('is_published')
                            ->label('Publié')
                            ->required(),
                        DateTimePicker::make('published_at')
                            ->label('Début de publication'),
                        TextInput::make('seo_title')
                            ->label('Titre SEO'),
                        Textarea::make('seo_description')
                            ->label('Description SEO')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Titre')
                    ->searchable(),
                ImageColumn::make('image_path')
                    ->disk('public')
                    ->label('Image'),
                TextColumn::make('starts_at')
                    ->label('Date et heure')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('venue.name')
                    ->label('Lieu')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn (Event $record): string => $record->statusLabel()),
                IconColumn::make('is_featured')
                    ->label('Une')
                    ->boolean(),
                IconColumn::make('is_published')
                    ->label('Publié')
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('starts_at')
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
            'index' => ManageEvents::route('/'),
        ];
    }
}
