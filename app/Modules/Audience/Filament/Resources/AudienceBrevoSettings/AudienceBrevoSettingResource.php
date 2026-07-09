<?php

namespace App\Modules\Audience\Filament\Resources\AudienceBrevoSettings;

use App\Modules\Audience\Filament\Resources\AudienceBrevoSettings\Pages\ManageAudienceBrevoSettings;
use App\Modules\Audience\Models\AudienceBrevoSetting;
use App\Modules\Audience\Services\BrevoAudienceService;
use App\Support\Modules;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema as SchemaFacade;
use UnitEnum;

class AudienceBrevoSettingResource extends Resource
{
    protected static ?string $model = AudienceBrevoSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?string $navigationLabel = 'Brevo';

    protected static UnitEnum|string|null $navigationGroup = 'Relation client';

    protected static ?string $modelLabel = 'réglage Brevo';

    protected static ?string $pluralModelLabel = 'réglages Brevo';

    protected static ?int $navigationSort = 50;

    public static function shouldRegisterNavigation(): bool
    {
        return Modules::enabled('audience') && self::hasBrevoSettingsTable();
    }

    public static function canAccess(): bool
    {
        return Modules::enabled('audience') && self::hasBrevoSettingsTable() && parent::canAccess();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Toggle::make('is_enabled')
                    ->label('Activer Brevo')
                    ->default(false),
                TextInput::make('api_key_encrypted')
                    ->label('Clé API Brevo')
                    ->password()
                    ->revealable()
                    ->helperText(fn (?AudienceBrevoSetting $record): string => $record?->hasApiKey()
                        ? 'Une clé API est déjà enregistrée. Laissez vide pour la conserver.'
                        : 'Collez la clé API Brevo. Elle sera stockée chiffrée.')
                    ->dehydrated(fn (?string $state, ?AudienceBrevoSetting $record): bool => filled($state) || $record === null)
                    ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? $state : null),
                TextInput::make('sender_name')
                    ->label('Nom expéditeur par défaut'),
                TextInput::make('sender_email')
                    ->label('Email expéditeur par défaut')
                    ->email(),
                TextInput::make('reply_to_email')
                    ->label('Email de réponse par défaut')
                    ->email(),
                TextInput::make('default_folder_id')
                    ->label('Dossier Brevo par défaut')
                    ->numeric(),
                TextInput::make('webhook_secret')
                    ->label('Secret webhook')
                    ->disabled()
                    ->dehydrated(),
                TextInput::make('last_connection_test_status')
                    ->label('Statut du dernier test')
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('last_connection_test_message')
                    ->label('Message du dernier test')
                    ->disabled()
                    ->columnSpanFull()
                    ->dehydrated(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('is_enabled')
                    ->label('Actif')
                    ->boolean(),
                TextColumn::make('sender_email')
                    ->label('Expéditeur'),
                TextColumn::make('last_connection_test_status')
                    ->label('Dernier test')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        AudienceBrevoSetting::TEST_STATUS_SUCCESS => 'success',
                        AudienceBrevoSetting::TEST_STATUS_FAILED => 'danger',
                        AudienceBrevoSetting::TEST_STATUS_MISSING_KEY => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('last_connection_test_at')
                    ->label('Testé le')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Modifié le')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('testConnection')
                    ->label('Tester')
                    ->icon(Heroicon::OutlinedPlay)
                    ->action(function (AudienceBrevoSetting $record): void {
                        $result = app(BrevoAudienceService::class)->testConnection($record);

                        $record->forceFill([
                            'last_connection_test_at' => now(),
                            'last_connection_test_status' => $result['status'],
                            'last_connection_test_message' => $result['message'],
                        ])->save();

                        $notification = Notification::make()
                            ->title($result['ok'] ? 'Connexion Brevo validée' : 'Connexion Brevo en erreur')
                            ->body($result['message']);

                        $result['ok']
                            ? $notification->success()->send()
                            : $notification->danger()->send();
                    }),
                EditAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->oldest('id')->limit(1);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAudienceBrevoSettings::route('/'),
        ];
    }

    private static function hasBrevoSettingsTable(): bool
    {
        return SchemaFacade::hasTable('audience_brevo_settings');
    }
}
