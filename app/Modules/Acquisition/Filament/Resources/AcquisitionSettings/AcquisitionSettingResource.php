<?php

namespace App\Modules\Acquisition\Filament\Resources\AcquisitionSettings;

use App\Modules\Acquisition\Filament\Resources\AcquisitionSettings\Pages\ManageAcquisitionSettings;
use App\Modules\Acquisition\Models\AcquisitionSetting;
use App\Support\Modules;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class AcquisitionSettingResource extends Resource
{
    protected static ?string $model = AcquisitionSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBarSquare;

    protected static ?string $navigationLabel = 'Acquisition';

    protected static UnitEnum|string|null $navigationGroup = 'Marketing';

    protected static ?string $modelLabel = "configuration de l'acquisition";

    protected static ?string $pluralModelLabel = 'acquisition';

    protected static ?int $navigationSort = 10;

    public static function shouldRegisterNavigation(): bool
    {
        return Modules::enabled('acquisition');
    }

    public static function canAccess(): bool
    {
        return Modules::enabled('acquisition') && parent::canAccess();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Mesure du site')
                ->description('Le site connaît seulement le conteneur GTM. Les identifiants Analytics et Ads restent configurés chez Google.')
                ->schema([
                    Toggle::make('is_enabled')
                        ->label('Activer la mesure')
                        ->live(),
                    TextInput::make('gtm_container_id')
                        ->label('Identifiant Google Tag Manager')
                        ->placeholder('GTM-ABC1234')
                        ->maxLength(32)
                        ->helperText("L'activation exige un identifiant commençant par GTM-."),
                ])
                ->columns(2),
            Section::make('Consentement')
                ->description("Le mode basic bloque les balises jusqu'au choix positif. Le mode advanced nécessite une décision explicite avant son activation en production.")
                ->schema([
                    Toggle::make('consent_enabled')
                        ->label('Demander le consentement'),
                    Select::make('consent_mode')
                        ->label('Mode de consentement')
                        ->options([
                            'basic' => 'Basic — aucune mesure avant acceptation',
                            'advanced' => 'Advanced — signaux limités avant acceptation',
                        ])
                        ->required(),
                    TextInput::make('privacy_policy_url')
                        ->label('Politique de confidentialité')
                        ->url()
                        ->maxLength(2048)
                        ->columnSpanFull(),
                ])
                ->columns(2),
            Section::make('Rapports')
                ->description("Ces valeurs servent aux dates et montants du futur tableau de bord. Elles n'ouvrent aucune connexion Google.")
                ->schema([
                    TextInput::make('timezone')
                        ->label('Fuseau horaire')
                        ->required()
                        ->maxLength(64)
                        ->helperText('Par exemple Europe/Paris ou America/Cuiaba.'),
                    TextInput::make('currency')
                        ->label('Devise')
                        ->required()
                        ->length(3)
                        ->placeholder('EUR'),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('is_enabled')->label('Mesure active')->boolean(),
                TextColumn::make('gtm_container_id')->label('Conteneur GTM')->placeholder('Non configuré'),
                TextColumn::make('consent_mode')
                    ->label('Consentement')
                    ->formatStateUsing(fn (string $state): string => $state === 'advanced' ? 'Advanced' : 'Basic'),
                TextColumn::make('timezone')->label('Fuseau horaire'),
                TextColumn::make('currency')->label('Devise'),
            ])
            ->recordActions([
                EditAction::make()->label('Configurer'),
            ])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAcquisitionSettings::route('/'),
        ];
    }
}
