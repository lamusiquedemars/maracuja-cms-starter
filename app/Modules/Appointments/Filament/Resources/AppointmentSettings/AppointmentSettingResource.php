<?php

namespace App\Modules\Appointments\Filament\Resources\AppointmentSettings;

use App\Modules\Appointments\Enums\AppointmentMode;
use App\Modules\Appointments\Enums\AppointmentProvider;
use App\Modules\Appointments\Filament\Resources\AppointmentSettings\Pages\ManageAppointmentSettings;
use App\Modules\Appointments\Models\AppointmentSetting;
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

class AppointmentSettingResource extends Resource
{
    protected static ?string $model = AppointmentSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Rendez-vous';

    protected static UnitEnum|string|null $navigationGroup = 'Accueil';

    protected static ?string $modelLabel = 'configuration des rendez-vous';

    protected static ?string $pluralModelLabel = 'configuration des rendez-vous';

    protected static ?int $navigationSort = 20;

    public static function shouldRegisterNavigation(): bool
    {
        return Modules::enabled('appointments');
    }

    public static function canAccess(): bool
    {
        return Modules::enabled('appointments') && parent::canAccess();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Page de réservation')
                ->description('Maracuja conserve le parcours et délègue la gestion des créneaux au fournisseur configuré.')
                ->schema([
                    Toggle::make('is_enabled')
                        ->label('Activer les rendez-vous')
                        ->live(),
                    Select::make('provider')
                        ->label('Fournisseur')
                        ->options(self::providerOptions())
                        ->required(),
                    Select::make('mode')
                        ->label('Mode de réservation')
                        ->options(self::modeOptions())
                        ->required(),
                    TextInput::make('booking_url')
                        ->label('Adresse de la page de réservation')
                        ->url()
                        ->maxLength(2048)
                        ->helperText('Aucune donnée personnelle ou demande ne sera ajoutée à cette adresse.')
                        ->columnSpanFull(),
                    TextInput::make('timezone')
                        ->label('Fuseau horaire professionnel')
                        ->required()
                        ->helperText('Identifiant IANA, par exemple Europe/Paris ou America/Cuiaba.'),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('is_enabled')->label('Actif')->boolean(),
                TextColumn::make('provider')
                    ->label('Fournisseur')
                    ->formatStateUsing(fn (AppointmentProvider $state): string => $state->label()),
                TextColumn::make('mode')
                    ->label('Mode')
                    ->formatStateUsing(fn (AppointmentMode $state): string => $state->label()),
                TextColumn::make('timezone')->label('Fuseau horaire'),
                TextColumn::make('booking_url')->label('Page de réservation')->limit(45),
            ])
            ->recordActions([
                EditAction::make()->label('Configurer'),
            ])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAppointmentSettings::route('/'),
        ];
    }

    private static function providerOptions(): array
    {
        return collect(AppointmentProvider::cases())
            ->mapWithKeys(fn (AppointmentProvider $provider): array => [$provider->value => $provider->label()])
            ->all();
    }

    private static function modeOptions(): array
    {
        return collect(AppointmentMode::cases())
            ->mapWithKeys(fn (AppointmentMode $mode): array => [$mode->value => $mode->label()])
            ->all();
    }
}
