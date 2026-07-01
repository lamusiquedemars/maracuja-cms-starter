<?php

namespace App\Filament\Resources\ContentSlots;

use App\Filament\Resources\ContentSlots\Pages\ManageContentSlots;
use App\Modules\ContentSlots\Models\ContentSlot;
use App\Support\Modules;
use BackedEnum;
use UnitEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

class ContentSlotResource extends Resource
{
    protected static ?string $model = ContentSlot::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPencilSquare;

    protected static ?string $navigationLabel = 'Contenus courts';

    protected static UnitEnum|string|null $navigationGroup = 'Contenus';

    protected static ?string $modelLabel = 'contenu court';

    protected static ?string $pluralModelLabel = 'contenus courts';

    protected static ?int $navigationSort = 16;

    public static function shouldRegisterNavigation(): bool
    {
        return Modules::enabled('content_slots');
    }

    public static function canAccess(): bool
    {
        return Modules::enabled('content_slots') && parent::canAccess();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('label')
                    ->label('Nom visible')
                    ->required()
                    ->maxLength(120)
                    ->disabled(fn (?ContentSlot $record): bool => (bool) $record?->is_locked)
                    ->dehydrated()
                    ->helperText('Libellé affiché dans l’admin. Les slots fournis par le starter sont verrouillés.'),
                TextInput::make('key')
                    ->label('Clé technique')
                    ->required()
                    ->maxLength(120)
                    ->unique(ignoreRecord: true)
                    ->disabled(fn (?ContentSlot $record): bool => (bool) $record?->is_locked)
                    ->dehydrated()
                    ->helperText('Nom utilisé dans les templates Blade. Ne doit pas être modifié après usage.'),
                TextInput::make('group')
                    ->label('Groupe')
                    ->required()
                    ->maxLength(80)
                    ->default('General')
                    ->disabled(fn (?ContentSlot $record): bool => (bool) $record?->is_locked)
                    ->dehydrated(),
                Select::make('type')
                    ->label('Type')
                    ->options([
                        'text' => 'Texte court',
                        'textarea' => 'Texte long',
                        'price' => 'Prix',
                        'date' => 'Date',
                    ])
                    ->required()
                    ->default('text')
                    ->disabled(fn (?ContentSlot $record): bool => (bool) $record?->is_locked)
                    ->dehydrated(),
                Textarea::make('value')
                    ->label('Contenu')
                    ->rows(3)
                    ->maxLength(1200)
                    ->columnSpanFull()
                    ->helperText('Zone courte et contrôlée. Le template décide où et comment ce contenu s’affiche.'),
                Textarea::make('help_text')
                    ->label('Aide admin')
                    ->rows(2)
                    ->columnSpanFull()
                    ->disabled(fn (?ContentSlot $record): bool => (bool) $record?->is_locked)
                    ->dehydrated(),
                Toggle::make('is_locked')
                    ->label('Clé verrouillée')
                    ->disabled()
                    ->dehydrated(false)
                    ->helperText('Verrouiller les slots fournis par le starter pour éviter de casser les templates.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('group')
                    ->label('Page / module')
                    ->sortable(),
                TextColumn::make('label')
                    ->label('Nom')
                    ->searchable(),
                TextColumn::make('key')
                    ->label('Clé')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('value')
                    ->label('Contenu')
                    ->limit(56)
                    ->searchable(),
                TextColumn::make('type')
                    ->label('Type'),
                IconColumn::make('is_locked')
                    ->label('Verrou')
                    ->boolean(),
            ])
            ->defaultSort('group')
            ->groups([
                Group::make('group')
                    ->label('Page / module')
                    ->titlePrefixedWithLabel(false)
                    ->collapsible(),
            ])
            ->defaultGroup('group')
            ->filters([
                SelectFilter::make('group')
                    ->label('Page / module')
                    ->options(fn (): array => ContentSlot::query()
                        ->orderBy('group')
                        ->pluck('group', 'group')
                        ->all()),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn (ContentSlot $record): bool => ! $record->is_locked),
            ])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageContentSlots::route('/'),
        ];
    }
}
