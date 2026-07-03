<?php

namespace App\Modules\Audience\Filament\Resources\AudienceContacts;

use App\Modules\Audience\Actions\CreateSegmentFromContacts;
use App\Modules\Audience\Filament\Resources\AudienceContacts\Pages\ManageAudienceContacts;
use App\Modules\Audience\Models\AudienceContact;
use App\Modules\Audience\Models\AudienceSegment;
use App\Support\Modules;
use BackedEnum;
use UnitEnum;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Schema as SchemaFacade;

class AudienceContactResource extends Resource
{
    protected static ?string $model = AudienceContact::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $navigationLabel = 'Contacts';

    protected static UnitEnum|string|null $navigationGroup = 'Relation client';

    protected static ?string $modelLabel = 'contact';

    protected static ?string $pluralModelLabel = 'contacts';

    protected static ?int $navigationSort = 20;

    public static function shouldRegisterNavigation(): bool
    {
        return Modules::enabled('audience') && self::hasAudienceTables();
    }

    public static function canAccess(): bool
    {
        return Modules::enabled('audience') && self::hasAudienceTables() && parent::canAccess();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('first_name')
                    ->label('Prénom'),
                TextInput::make('last_name')
                    ->label('Nom'),
                TextInput::make('organization_name')
                    ->label('Organisation'),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required(),
                Select::make('segments')
                    ->label('Segments')
                    ->relationship('segments', 'name')
                    ->multiple()
                    ->preload(),
                Toggle::make('accepts_email')
                    ->label('Accepte les emails')
                    ->default(true),
                Textarea::make('notes')
                    ->label('Notes')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('first_name')
                    ->label('Prénom')
                    ->searchable(),
                TextColumn::make('last_name')
                    ->label('Nom')
                    ->searchable(),
                TextColumn::make('organization_name')
                    ->label('Organisation')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                IconColumn::make('accepts_email')
                    ->label('Emails')
                    ->boolean(),
                TextColumn::make('segments.name')
                    ->label('Segments')
                    ->badge()
                    ->separator(',')
                    ->toggleable(),
                TextColumn::make('last_contacted_at')
                    ->label('Dernier envoi')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('segment')
                    ->label('Segment')
                    ->relationship('segments', 'name')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('accepts_email')
                    ->label('Accepte les emails'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('addToSegment')
                        ->label('Ajouter à un segment')
                        ->icon(Heroicon::OutlinedPlusCircle)
                        ->form([
                            Select::make('segment_id')
                                ->label('Segment')
                                ->options(fn (): array => AudienceSegment::query()
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all())
                                ->searchable()
                                ->required(),
                        ])
                        ->action(function (EloquentCollection $records, array $data): void {
                            $segment = AudienceSegment::query()->findOrFail($data['segment_id']);

                            $segment->contacts()->syncWithoutDetaching($records->pluck('id')->all());

                            Notification::make()
                                ->title('Contacts ajoutés')
                                ->body("{$records->count()} contact(s) ajouté(s) au segment « {$segment->name} ».")
                                ->success()
                                ->send();
                        }),
                    BulkAction::make('removeFromSegment')
                        ->label('Retirer d’un segment')
                        ->icon(Heroicon::OutlinedMinusCircle)
                        ->form([
                            Select::make('segment_id')
                                ->label('Segment')
                                ->options(fn (): array => AudienceSegment::query()
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all())
                                ->searchable()
                                ->required(),
                        ])
                        ->action(function (EloquentCollection $records, array $data): void {
                            $segment = AudienceSegment::query()->findOrFail($data['segment_id']);

                            $segment->contacts()->detach($records->pluck('id')->all());

                            Notification::make()
                                ->title('Contacts retirés')
                                ->body("{$records->count()} contact(s) retiré(s) du segment « {$segment->name} ».")
                                ->success()
                                ->send();
                        }),
                    BulkAction::make('createSegment')
                        ->label('Créer un segment')
                        ->icon(Heroicon::OutlinedRectangleGroup)
                        ->form([
                            TextInput::make('name')
                                ->label('Nom du segment')
                                ->required(),
                            Textarea::make('description')
                                ->label('Description'),
                        ])
                        ->action(function (EloquentCollection $records, array $data): void {
                            $segment = CreateSegmentFromContacts::run(
                                name: (string) $data['name'],
                                description: $data['description'] ?? null,
                                contactIds: $records->pluck('id'),
                            );

                            Notification::make()
                                ->title('Segment créé')
                                ->body("Le segment « {$segment->name} » contient {$records->count()} contact(s).")
                                ->success()
                                ->send();
                        }),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAudienceContacts::route('/'),
        ];
    }

    private static function hasAudienceTables(): bool
    {
        return SchemaFacade::hasTable('audience_contacts') && SchemaFacade::hasTable('audience_contact_segment');
    }
}
