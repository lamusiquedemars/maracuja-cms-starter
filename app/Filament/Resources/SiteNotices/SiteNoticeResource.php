<?php

namespace App\Filament\Resources\SiteNotices;

use App\Filament\Resources\SiteNotices\Pages\ManageSiteNotices;
use App\Modules\Notices\Models\SiteNotice;
use App\Support\Modules;
use BackedEnum;
use UnitEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SiteNoticeResource extends Resource
{
    protected static ?string $model = SiteNotice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static ?string $navigationLabel = 'Annonce courte';

    protected static UnitEnum|string|null $navigationGroup = 'Contenus';

    protected static ?string $modelLabel = 'annonce courte';

    protected static ?string $pluralModelLabel = 'annonces courtes';

    protected static ?int $navigationSort = 15;

    public static function shouldRegisterNavigation(): bool
    {
        return Modules::enabled('notices');
    }

    public static function canAccess(): bool
    {
        return Modules::enabled('notices') && parent::canAccess();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Titre court')
                    ->columnSpanFull()
                    ->maxLength(120)
                    ->helperText('Optionnel. Exemple: Horaires d’été.'),
                Textarea::make('message')
                    ->label('Message')
                    ->required()
                    ->maxLength(300)
                    ->rows(3)
                    ->columnSpanFull()
                    ->helperText('Texte court uniquement. Ce module sert à annoncer une information, pas à redessiner la page.'),
                TextInput::make('link_label')
                    ->label('Libellé du lien')
                    ->maxLength(80),
                TextInput::make('link_url')
                    ->label('URL du lien')
                    ->maxLength(255)
                    ->helperText('Optionnel. Utiliser une URL interne comme /contact ou une URL complète.'),
                Select::make('placement')
                    ->label('Emplacement')
                    ->options([
                        'home' => 'Accueil',
                    ])
                    ->default('home')
                    ->required(),
                Select::make('tone')
                    ->label('Style')
                    ->options([
                        'info' => 'Info',
                        'success' => 'Positif',
                        'warning' => 'Important',
                    ])
                    ->default('info')
                    ->required(),
                Toggle::make('is_published')
                    ->label('Publié')
                    ->required(),
                DateTimePicker::make('starts_at')
                    ->label('Début')
                    ->helperText('Laisser vide pour afficher dès publication.'),
                DateTimePicker::make('ends_at')
                    ->label('Fin')
                    ->helperText('Laisser vide pour ne pas expirer automatiquement.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Titre')
                    ->placeholder('Sans titre')
                    ->searchable(),
                TextColumn::make('message')
                    ->label('Message')
                    ->limit(48)
                    ->searchable(),
                TextColumn::make('placement')
                    ->label('Emplacement'),
                IconColumn::make('is_published')
                    ->label('Publié')
                    ->boolean(),
                TextColumn::make('starts_at')
                    ->label('Début')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->label('Fin')
                    ->dateTime()
                    ->sortable(),
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
            'index' => ManageSiteNotices::route('/'),
        ];
    }
}
