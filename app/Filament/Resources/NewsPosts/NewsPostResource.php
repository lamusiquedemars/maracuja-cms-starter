<?php

namespace App\Filament\Resources\NewsPosts;

use App\Filament\Resources\NewsPosts\Pages\ManageNewsPosts;
use App\Modules\News\Models\NewsPost;
use App\Support\MediaFiles;
use App\Support\Modules;
use BackedEnum;
use UnitEnum;
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
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class NewsPostResource extends Resource
{
    protected static ?string $model = NewsPost::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Actualités';

    protected static UnitEnum|string|null $navigationGroup = 'Contenus';

    protected static ?string $modelLabel = 'actualité';

    protected static ?string $pluralModelLabel = 'actualités';

    protected static ?int $navigationSort = 20;

    public static function shouldRegisterNavigation(): bool
    {
        return Modules::enabled('news');
    }

    public static function canAccess(): bool
    {
        return Modules::enabled('news') && parent::canAccess();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Titre')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                Textarea::make('excerpt')
                    ->label('Résumé')
                    ->columnSpanFull(),
                RichEditor::make('content')
                    ->label('Contenu')
                    ->columnSpanFull(),
                Select::make('existing_image_path')
                    ->label('Choisir une image existante')
                    ->options(fn (): array => MediaFiles::options('news'))
                    ->searchable()
                    ->live()
                    ->dehydrated(false)
                    ->afterStateUpdated(fn (Set $set, ?string $state): mixed => filled($state) ? $set('image_path', $state) : null)
                    ->helperText('Liste les fichiers déjà présents dans storage/app/public/news.'),
                FileUpload::make('image_path')
                    ->label('Image')
                    ->disk('public')
                    ->directory('news')
                    ->visibility('public')
                    ->fetchFileInformation(false)
                    ->preventFilePathTampering(true, fn (string $file): bool => MediaFiles::isAllowed($file, 'news'))
                    ->image(),
                Toggle::make('is_published')
                    ->label('Publié')
                    ->required(),
                Toggle::make('is_pinned')
                    ->label('Épingler')
                    ->helperText('Remonte cette actualité dans les listes.'),
                Toggle::make('has_detail_page')
                    ->label('Page détail')
                    ->default(true)
                    ->helperText('Désactiver pour une annonce courte visible seulement dans les listes.'),
                DateTimePicker::make('published_at')
                    ->label('Début de publication'),
                DateTimePicker::make('expires_at')
                    ->label('Fin de publication')
                    ->default(fn () => now()->addDays((int) config('maracuja.news.default_duration_days', 30)))
                    ->helperText('Optionnel. Par défaut, une nouvelle actualité expire selon la durée configurée du starter.'),
                TextInput::make('seo_title')
                    ->label('Titre SEO')
                    ->visible(fn (?NewsPost $record): bool => $record?->has_detail_page !== false),
                Textarea::make('seo_description')
                    ->label('Description SEO')
                    ->columnSpanFull()
                    ->visible(fn (?NewsPost $record): bool => $record?->has_detail_page !== false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Titre')
                    ->searchable(),
                TextColumn::make('slug')
                    ->searchable(),
                ImageColumn::make('image_path')
                    ->disk('public'),
                IconColumn::make('is_published')
                    ->label('Publié')
                    ->boolean(),
                IconColumn::make('is_pinned')
                    ->label('Épinglé')
                    ->boolean()
                    ->sortable(),
                IconColumn::make('has_detail_page')
                    ->label('Détail')
                    ->boolean(),
                TextColumn::make('published_at')
                    ->label('Début')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->label('Fin')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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
            'index' => ManageNewsPosts::route('/'),
        ];
    }
}
