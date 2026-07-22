<?php

namespace App\Modules\Media\Filament\Resources\MediaAssets;

use App\Modules\Media\Enums\MediaType;
use App\Modules\Media\Filament\Resources\MediaAssets\Pages\ManageMediaAssets;
use App\Modules\Media\Models\MediaAsset;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema as SchemaFacade;
use UnitEnum;

class MediaAssetResource extends Resource
{
    protected static ?string $model = MediaAsset::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static ?string $navigationLabel = 'Médias';

    protected static UnitEnum|string|null $navigationGroup = 'Médias';

    protected static ?string $modelLabel = 'média';

    protected static ?string $pluralModelLabel = 'médias';

    protected static ?int $navigationSort = 10;

    public static function shouldRegisterNavigation(): bool
    {
        return SchemaFacade::hasTable('media_assets');
    }

    public static function canAccess(): bool
    {
        return SchemaFacade::hasTable('media_assets') && parent::canAccess();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informations éditoriales')
                ->schema([
                    TextInput::make('display_name')
                        ->label('Nom')
                        ->columnSpanFull()
                        ->required()
                        ->maxLength(255),
                    Textarea::make('alt_text')
                        ->label('Texte alternatif')
                        ->rows(2)
                        ->visible(fn (?MediaAsset $record): bool => $record?->isImage() ?? true)
                        ->helperText('Décrire l’information apportée par l’image. Laisser vide pour une image décorative.'),
                    Textarea::make('caption')
                        ->label('Légende')
                        ->rows(2),
                    TextInput::make('credit')
                        ->label('Crédit')
                        ->maxLength(255),
                ])
                ->columns(2),
            Section::make('Fichier')
                ->schema([
                    TextInput::make('original_name')
                        ->label('Nom original')
                        ->disabled()
                        ->dehydrated(false),
                    TextInput::make('path')
                        ->label('Chemin')
                        ->disabled()
                        ->dehydrated(false),
                    TextInput::make('mime_type')
                        ->label('Type MIME')
                        ->disabled()
                        ->dehydrated(false),
                    TextInput::make('technical_details')
                        ->label('Dimensions et poids')
                        ->formatStateUsing(fn (?MediaAsset $record): string => collect([
                            $record?->dimensionsLabel(),
                            $record?->formattedSize(),
                        ])->filter()->implode(' · '))
                        ->disabled()
                        ->dehydrated(false),
                ])
                ->columns(2)
                ->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withCount('usages'))
            ->columns([
                ViewColumn::make('preview')
                    ->label('Aperçu')
                    ->view('filament.media.columns.preview'),
                Stack::make([
                    TextColumn::make('display_name')
                        ->label('Nom')
                        ->weight('semibold')
                        ->searchable(['display_name', 'original_name', 'path', 'alt_text', 'caption', 'credit'])
                        ->wrap(),
                    TextColumn::make('original_name')
                        ->label('Fichier')
                        ->color('gray')
                        ->size('sm')
                        ->limit(40),
                    TextColumn::make('type')
                        ->badge()
                        ->formatStateUsing(fn (MediaType $state): string => $state->label())
                        ->color(fn (MediaType $state): string => $state === MediaType::Image ? 'info' : 'gray'),
                    TextColumn::make('details')
                        ->state(fn (MediaAsset $record): string => collect([
                            $record->dimensionsLabel(),
                            $record->formattedSize(),
                        ])->filter()->implode(' · '))
                        ->color('gray')
                        ->size('sm'),
                    TextColumn::make('usages_count')
                        ->label('Utilisations')
                        ->formatStateUsing(fn (int $state): string => $state === 0 ? 'Non utilisé' : $state.' utilisation'.($state > 1 ? 's' : ''))
                        ->color(fn (int $state): string => $state === 0 ? 'gray' : 'success')
                        ->size('sm'),
                ])->space(2),
            ])
            ->contentGrid([
                'sm' => 2,
                'lg' => 3,
                '2xl' => 4,
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        MediaType::Image->value => 'Images',
                        MediaType::Document->value => 'Documents',
                    ]),
                TernaryFilter::make('used')
                    ->label('Utilisation')
                    ->placeholder('Tous les médias')
                    ->trueLabel('Utilisés')
                    ->falseLabel('Non utilisés')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->has('usages'),
                        false: fn (Builder $query): Builder => $query->doesntHave('usages'),
                        blank: fn (Builder $query): Builder => $query,
                    ),
            ])
            ->recordAction('edit')
            ->recordActions([
                Action::make('preview')
                    ->label('Aperçu')
                    ->icon(Heroicon::OutlinedEye)
                    ->modalHeading(fn (MediaAsset $record): string => $record->display_name)
                    ->modalWidth(Width::FourExtraLarge)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fermer')
                    ->modalContent(fn (MediaAsset $record) => view('filament.media.preview', ['media' => $record])),
                EditAction::make()
                    ->label('Modifier'),
                DeleteAction::make()
                    ->label('Supprimer')
                    ->disabled(fn (MediaAsset $record): bool => ! $record->canBeDeleted())
                    ->tooltip(fn (MediaAsset $record): ?string => $record->canBeDeleted()
                        ? null
                        : 'Ce média est encore utilisé et ne peut pas être supprimé.'),
            ])
            ->toolbarActions([])
            ->emptyStateHeading('Aucun média')
            ->emptyStateDescription('Ajoutez une image ou un document public à la médiathèque.');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageMediaAssets::route('/'),
        ];
    }
}
