<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\Pages\Pages\ManagePages;
use App\Modules\Pages\Models\Page;
use App\Support\MediaFiles;
use App\Support\Modules;
use BackedEnum;
use UnitEnum;
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

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Pages';

    protected static UnitEnum|string|null $navigationGroup = 'Contenus';

    protected static ?string $modelLabel = 'page';

    protected static ?string $pluralModelLabel = 'pages';

    protected static ?int $navigationSort = 10;

    public static function shouldRegisterNavigation(): bool
    {
        return Modules::enabled('pages');
    }

    public static function canAccess(): bool
    {
        return Modules::enabled('pages')
            && parent::canAccess();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Titre')
                    ->required()
                    ->helperText('Nom de la page dans le registre. La structure reste cadrée par son type.'),
                TextInput::make('slug')
                    ->label('Slug')
                    ->helperText('URL publique ou identifiant de route. Géré par le développeur.')
                    ->disabled()
                    ->dehydrated()
                    ->required(),
                Select::make('type')
                    ->label('Type de page')
                    ->options([
                        Page::TYPE_SYSTEM => 'Page système',
                        Page::TYPE_TEXT => 'Page texte',
                        Page::TYPE_MODULE => 'Page module',
                    ])
                    ->helperText('Le type définit ce que l’admin peut modifier.')
                    ->disabled()
                    ->dehydrated()
                    ->required()
                    ->default(Page::TYPE_SYSTEM),
                Select::make('template')
                    ->label('Template')
                    ->options([
                        'default' => 'Page simple',
                        'landing' => 'Page avec sections',
                        'services' => 'Services / offres',
                        'contact' => 'Contact',
                        'module' => 'Module',
                    ])
                    ->helperText('Template ou route associée. Géré par le développeur.')
                    ->visible(fn (?Page $record): bool => ! $record?->isText())
                    ->disabled()
                    ->dehydrated()
                    ->required()
                    ->default('default'),
                Textarea::make('excerpt')
                    ->label('Résumé')
                    ->columnSpanFull()
                    ->helperText('Résumé éditorial de la page. Peut servir de fallback pour le hero ou le SEO.'),
                TextInput::make('hero_title')
                    ->label('Titre hero')
                    ->helperText('Titre principal affiché dans le hero de la page.'),
                Textarea::make('hero_subtitle')
                    ->label('Sous-titre hero')
                    ->columnSpanFull()
                    ->helperText('Sous-titre affiché sous le titre hero.'),
                Select::make('existing_hero_image_path')
                    ->label('Choisir une image hero existante')
                    ->options(fn (): array => MediaFiles::options('pages'))
                    ->searchable()
                    ->live()
                    ->dehydrated(false)
                    ->afterStateUpdated(fn (Set $set, ?string $state): mixed => filled($state) ? $set('hero_image_path', $state) : null)
                    ->helperText('Liste les fichiers déjà présents dans storage/app/public/pages.'),
                FileUpload::make('hero_image_path')
                    ->label('Image hero')
                    ->disk('public')
                    ->directory('pages')
                    ->visibility('public')
                    ->fetchFileInformation(false)
                    ->preventFilePathTampering(true, fn (string $file): bool => MediaFiles::isAllowed($file, 'pages'))
                    ->image()
                    ->helperText('Image stockée publiquement dans storage/app/public/pages. Nécessite le lien public/storage.'),
                RichEditor::make('content')
                    ->label('Texte principal')
                    ->visible(fn (?Page $record): bool => (bool) $record?->isText())
                    ->columnSpanFull()
                    ->helperText('Texte simple de page cadrée. Pas de sections, pas de blocs libres.'),
                TextInput::make('seo_title')
                    ->label('Titre SEO')
                    ->helperText('Titre utilisé par les moteurs de recherche et les partages.'),
                Textarea::make('seo_description')
                    ->label('Description SEO')
                    ->columnSpanFull(),
                Toggle::make('is_published')
                    ->label('Publié')
                    ->required(),
                DateTimePicker::make('published_at')
                    ->label('Date de publication'),
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
                TextColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Page::TYPE_SYSTEM => 'Page système',
                        Page::TYPE_TEXT => 'Page texte',
                        Page::TYPE_MODULE => 'Page module',
                        default => $state,
                    })
                    ->badge()
                    ->sortable(),
                TextColumn::make('template')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('hero_title')
                    ->searchable(),
                ImageColumn::make('hero_image_path')
                    ->disk('public'),
                TextColumn::make('seo_title')
                    ->searchable(),
                IconColumn::make('is_published')
                    ->label('Publié')
                    ->boolean(),
                TextColumn::make('published_at')
                    ->label('Publication')
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
            ])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePages::route('/'),
        ];
    }
}
