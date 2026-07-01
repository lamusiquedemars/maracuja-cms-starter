<?php

namespace App\Filament\Resources\Articles;

use App\Filament\Resources\Articles\Pages\ManageArticles;
use App\Modules\Articles\Models\Article;
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
use Filament\Forms\Components\Repeater;
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

class ArticleResource extends Resource
{
    protected static ?string $model = Article::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Articles';

    protected static UnitEnum|string|null $navigationGroup = 'Contenus';

    protected static ?string $modelLabel = 'article';

    protected static ?string $pluralModelLabel = 'articles';

    protected static ?int $navigationSort = 25;

    public static function shouldRegisterNavigation(): bool
    {
        return Modules::enabled('articles');
    }

    public static function canAccess(): bool
    {
        return Modules::enabled('articles') && parent::canAccess();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Titre')
                    ->required(),
                TextInput::make('slug')
                    ->required()
                    ->helperText('URL publique. Exemple : bois-bresiliens-archeterie.'),
                Textarea::make('excerpt')
                    ->label('Résumé')
                    ->columnSpanFull()
                    ->helperText('Utilisé pour la liste et le SEO si aucune description SEO n’est renseignée.'),
                Select::make('existing_image_path')
                    ->label('Choisir une image principale existante')
                    ->options(fn (): array => MediaFiles::options('articles'))
                    ->searchable()
                    ->live()
                    ->dehydrated(false)
                    ->afterStateUpdated(fn (Set $set, ?string $state): mixed => filled($state) ? $set('image_path', $state) : null)
                    ->helperText('Liste les fichiers déjà présents dans storage/app/public/articles.'),
                FileUpload::make('image_path')
                    ->label('Image principale')
                    ->disk('public')
                    ->directory('articles')
                    ->visibility('public')
                    ->fetchFileInformation(false)
                    ->preventFilePathTampering(true, fn (string $file): bool => MediaFiles::isAllowed($file, 'articles'))
                    ->image(),
                Repeater::make('body_blocks')
                    ->label('Blocs de contenu')
                    ->columnSpanFull()
                    ->addActionLabel('Ajouter un bloc')
                    ->reorderable()
                    ->schema([
                        Select::make('type')
                            ->label('Type de bloc')
                            ->options([
                                'rich_text' => 'Texte riche',
                                'heading' => 'Intertitre',
                                'image' => 'Image',
                                'quote' => 'Citation',
                                'note' => 'Note / encadré',
                                'table' => 'Tableau simple',
                            ])
                            ->default('rich_text')
                            ->required()
                            ->live(),
                        Select::make('level')
                            ->label('Niveau')
                            ->options([
                                '2' => 'H2',
                                '3' => 'H3',
                            ])
                            ->default('2')
                            ->visible(fn ($get): bool => $get('type') === 'heading'),
                        TextInput::make('heading')
                            ->label('Intertitre')
                            ->visible(fn ($get): bool => $get('type') === 'heading'),
                        RichEditor::make('text')
                            ->label('Texte')
                            ->visible(fn ($get): bool => $get('type') === 'rich_text')
                            ->columnSpanFull(),
                        Select::make('existing_image_path')
                            ->label('Choisir une image existante')
                            ->options(fn (): array => MediaFiles::options('articles/blocks'))
                            ->searchable()
                            ->live()
                            ->dehydrated(false)
                            ->afterStateUpdated(fn (Set $set, ?string $state): mixed => filled($state) ? $set('image_path', $state) : null)
                            ->visible(fn ($get): bool => $get('type') === 'image')
                            ->helperText('Liste les fichiers déjà présents dans storage/app/public/articles/blocks.'),
                        FileUpload::make('image_path')
                            ->label('Image')
                            ->disk('public')
                            ->directory('articles/blocks')
                            ->visibility('public')
                            ->fetchFileInformation(false)
                            ->preventFilePathTampering(true, fn (string $file): bool => MediaFiles::isAllowed($file, 'articles/blocks'))
                            ->image()
                            ->visible(fn ($get): bool => $get('type') === 'image'),
                        TextInput::make('alt')
                            ->label('Texte alternatif')
                            ->visible(fn ($get): bool => $get('type') === 'image'),
                        TextInput::make('caption')
                            ->label('Légende')
                            ->visible(fn ($get): bool => $get('type') === 'image'),
                        Textarea::make('quote')
                            ->label('Citation')
                            ->visible(fn ($get): bool => $get('type') === 'quote'),
                        TextInput::make('author')
                            ->label('Auteur')
                            ->visible(fn ($get): bool => $get('type') === 'quote'),
                        RichEditor::make('note')
                            ->label('Note')
                            ->visible(fn ($get): bool => $get('type') === 'note')
                            ->columnSpanFull(),
                        Textarea::make('table_rows')
                            ->label('Lignes du tableau')
                            ->helperText('Une ligne par rangée, colonnes séparées par |. Exemple : Bois | Densité | Usage')
                            ->visible(fn ($get): bool => $get('type') === 'table')
                            ->columnSpanFull(),
                    ]),
                TextInput::make('seo_title')
                    ->label('Titre SEO'),
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
                ImageColumn::make('image_path')
                    ->disk('public')
                    ->label('Image'),
                IconColumn::make('is_published')
                    ->label('Publié')
                    ->boolean(),
                TextColumn::make('published_at')
                    ->label('Publication')
                    ->dateTime()
                    ->sortable(),
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
            'index' => ManageArticles::route('/'),
        ];
    }
}
