<?php

namespace App\Filament\Resources\SiteSettings;

use App\Filament\Resources\SiteSettings\Pages\ManageSiteSettings;
use App\Modules\Media\Filament\Forms\Components\MediaPicker;
use App\Modules\SiteSettings\Models\SiteSetting;
use App\Support\Modules;
use BackedEnum;
use UnitEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SiteSettingResource extends Resource
{
    protected static ?string $model = SiteSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Paramètres';

    protected static UnitEnum|string|null $navigationGroup = 'Réglages';

    protected static ?string $modelLabel = 'paramètres';

    protected static ?string $pluralModelLabel = 'paramètres';

    protected static ?int $navigationSort = 90;

    public static function shouldRegisterNavigation(): bool
    {
        return Modules::enabled('site_settings');
    }

    public static function canAccess(): bool
    {
        return Modules::enabled('site_settings') && parent::canAccess();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('site_name')
                    ->label('Nom du site')
                    ->required()
                    ->default('Maracuja CMS'),
                TextInput::make('baseline')
                    ->label('Baseline'),
                TextInput::make('default_seo_title')
                    ->label('Titre SEO par défaut'),
                Textarea::make('default_seo_description')
                    ->label('Description SEO par défaut')
                    ->columnSpanFull(),
                TextInput::make('contact_email')
                    ->label('Email de contact')
                    ->email(),
                Toggle::make('contact_form_send_admin_email')
                    ->label('Envoyer une notification à l’admin')
                    ->default(true),
                Toggle::make('contact_form_send_confirmation_email')
                    ->label('Envoyer une confirmation au visiteur')
                    ->default(false),
                Toggle::make('contact_form_show_name')
                    ->label('Afficher le champ Nom')
                    ->default(true),
                Toggle::make('contact_form_show_phone')
                    ->label('Afficher le champ Téléphone')
                    ->default(true),
                Toggle::make('contact_form_show_subject')
                    ->label('Afficher le champ Sujet')
                    ->default(true),
                TextInput::make('phone')
                    ->label('Téléphone')
                    ->tel(),
                Textarea::make('address')
                    ->label('Adresse')
                    ->columnSpanFull(),
                MediaPicker::make('logo_media_id')
                    ->label('Logo')
                    ->relationship('logoMedia', 'display_name')
                    ->imagesOnly(),
                MediaPicker::make('favicon_media_id')
                    ->label('Favicon')
                    ->relationship('faviconMedia', 'display_name')
                    ->imagesOnly(),
                MediaPicker::make('default_og_media_id')
                    ->label('Image sociale par défaut')
                    ->helperText('Image utilisée par Open Graph si une page ou actualité n’en fournit pas.')
                    ->relationship('defaultOgMedia', 'display_name')
                    ->imagesOnly(),
                KeyValue::make('social_links')
                    ->label('Liens sociaux')
                    ->keyLabel('Libellé')
                    ->valueLabel('URL')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('site_name')
                    ->label('Nom')
                    ->searchable(),
                TextColumn::make('baseline')
                    ->searchable(),
                TextColumn::make('default_seo_title')
                    ->searchable(),
                TextColumn::make('contact_email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('logo_path')
                    ->searchable(),
                TextColumn::make('favicon_path')
                    ->searchable(),
                TextColumn::make('default_og_image_path')
                    ->label('Image sociale')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => ManageSiteSettings::route('/'),
        ];
    }
}
