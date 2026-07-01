<?php

namespace App\Filament\Resources\Galleries;

use App\Filament\Resources\Galleries\Pages\CreateGallery;
use App\Filament\Resources\Galleries\Pages\EditGallery;
use App\Filament\Resources\Galleries\Pages\ListGalleries;
use App\Filament\Resources\Galleries\RelationManagers\ImagesRelationManager;
use App\Modules\Gallery\Models\Gallery;
use App\Support\Modules;
use BackedEnum;
use UnitEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GalleryResource extends Resource
{
    protected static ?string $model = Gallery::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?string $navigationLabel = 'Galeries';

    protected static UnitEnum|string|null $navigationGroup = 'Médias';

    protected static ?string $modelLabel = 'galerie';

    protected static ?string $pluralModelLabel = 'galeries';

    protected static ?int $navigationSort = 30;

    public static function shouldRegisterNavigation(): bool
    {
        return Modules::enabled('gallery');
    }

    public static function canAccess(): bool
    {
        return Modules::enabled('gallery') && parent::canAccess();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Nom')
                    ->required(),
                TextInput::make('slug')
                    ->label('Slug')
                    ->helperText('Identifiant technique utilisé par les templates. Laisser vide pour le générer.')
                    ->disabled(fn (?Gallery $record): bool => (bool) $record?->isSystemGallery())
                    ->dehydrated()
                    ->unique(ignoreRecord: true),
                Textarea::make('intro')
                    ->label('Introduction')
                    ->columnSpanFull(),
                Toggle::make('is_published')
                    ->label('Publié')
                    ->required()
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Nom')
                    ->searchable(),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('images_count')
                    ->counts('images')
                    ->label('Photos')
                    ->sortable(),
                IconColumn::make('is_published')
                    ->label('Publié')
                    ->boolean(),
            ])
            ->defaultSort('position')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn (Gallery $record): bool => $record->isSystemGallery()),
            ])
            ->toolbarActions([]);
    }

    public static function getRelations(): array
    {
        return [
            ImagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGalleries::route('/'),
            'create' => CreateGallery::route('/create'),
            'edit' => EditGallery::route('/{record}/edit'),
        ];
    }
}
