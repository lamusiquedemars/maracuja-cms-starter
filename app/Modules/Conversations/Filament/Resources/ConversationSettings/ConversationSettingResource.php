<?php

namespace App\Modules\Conversations\Filament\Resources\ConversationSettings;

use App\Modules\Conversations\Filament\Resources\ConversationSettings\Pages\ManageConversationSettings;
use App\Modules\Conversations\Models\ConversationSetting;
use App\Support\Modules;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class ConversationSettingResource extends Resource
{
    protected static ?string $model = ConversationSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $navigationLabel = 'Accueil conversationnel';

    protected static UnitEnum|string|null $navigationGroup = 'Accueil';

    protected static ?string $modelLabel = 'réglage de l’accueil conversationnel';

    protected static ?string $pluralModelLabel = 'accueil conversationnel';

    protected static ?int $navigationSort = 10;

    public static function shouldRegisterNavigation(): bool
    {
        return Modules::enabled('conversations');
    }

    public static function canAccess(): bool
    {
        return Modules::enabled('conversations') && parent::canAccess();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Assistant')
                ->description('Définissez son rôle avec des champs cadrés. Les protections essentielles restent imposées par le starter.')
                ->schema([
                    Toggle::make('is_enabled')
                        ->label('Activer l’accueil conversationnel'),
                    TextInput::make('widget_button_label')
                        ->label('Libellé du bouton')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('widget_title')
                        ->label('Titre de la fenêtre')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Textarea::make('welcome_message')
                        ->label('Message d’accueil')
                        ->rows(2)
                        ->maxLength(1000)
                        ->helperText('Affiché dès l’ouverture, sans créer de conversation tant que le visiteur n’écrit pas.')
                        ->columnSpanFull(),
                    Textarea::make('privacy_notice')
                        ->label('Avertissement affiché au visiteur')
                        ->rows(2)
                        ->maxLength(1000)
                        ->columnSpanFull(),
                    Select::make('assistant_language')
                        ->label('Langue principale')
                        ->options([
                            'fr' => 'Français',
                            'en' => 'English',
                            'pt-BR' => 'Português (Brasil)',
                            'es' => 'Español',
                            'it' => 'Italiano',
                            'de' => 'Deutsch',
                        ])
                        ->searchable()
                        ->required(),
                    TextInput::make('assistant_tone')
                        ->label('Ton attendu')
                        ->required()
                        ->maxLength(255)
                        ->helperText('Par exemple : clair, chaleureux et concis.')
                        ->columnSpanFull(),
                    Textarea::make('organization_summary')
                        ->label('Contexte de l’organisation')
                        ->rows(4)
                        ->maxLength(3000)
                        ->helperText('Présentez l’activité, les services et les limites utiles à l’orientation.')
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Section::make('Qualification minimale')
                ->description('L’assistant pose une question à la fois et ne recueille que ce qui est utile.')
                ->schema([
                    CheckboxList::make('qualification_fields')
                        ->label('Informations que l’assistant peut demander')
                        ->options(ConversationSetting::QUALIFICATION_FIELDS)
                        ->columns(2)
                        ->columnSpanFull(),
                    Textarea::make('qualification_guidance')
                        ->label('Précisions de qualification')
                        ->rows(2)
                        ->maxLength(2000)
                        ->columnSpanFull(),
                    Textarea::make('sensitive_data_guidance')
                        ->label('Informations supplémentaires à ne pas demander')
                        ->rows(2)
                        ->maxLength(2000)
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Section::make('Orientation')
                ->description('L’assistant reconnaît le bon moment pour proposer les canaux, mais le visiteur conserve le choix.')
                ->schema([
                    CheckboxList::make('routing_triggers')
                        ->label('Quand proposer une orientation')
                        ->options(ConversationSetting::ROUTING_TRIGGERS)
                        ->columns(2)
                        ->columnSpanFull(),
                    Textarea::make('urgency_guidance')
                        ->label('Critères d’urgence propres au site')
                        ->rows(2)
                        ->maxLength(2000)
                        ->columnSpanFull(),
                    TextInput::make('expected_response_time')
                        ->label('Délai de réponse pouvant être annoncé')
                        ->maxLength(255)
                        ->helperText('Par exemple : pendant les heures d’ouverture ou sous un jour ouvré.')
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Section::make('WhatsApp')
                ->schema([
                    Toggle::make('whatsapp_enabled')
                        ->label('Proposer WhatsApp')
                        ->live(),
                    TextInput::make('whatsapp_number')
                        ->label('Numéro WhatsApp')
                        ->tel()
                        ->maxLength(40)
                        ->helperText('Format international, sans lien ni texte.'),
                    Textarea::make('whatsapp_message_template')
                        ->label('Message de passage vers WhatsApp')
                        ->rows(3)
                        ->maxLength(1000)
                        ->helperText('Utilisez {{reference}} pour inclure la référence de conversation.')
                        ->columnSpanFull(),
                    Textarea::make('whatsapp_contact_message_template')
                        ->label('Message utilisé par l’équipe pour contacter le visiteur')
                        ->rows(3)
                        ->maxLength(1000)
                        ->helperText('Utilisez {{reference}} pour inclure la référence de conversation.')
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Section::make('Demande de contact')
                ->schema([
                    Toggle::make('callback_enabled')
                        ->label('Permettre au visiteur de demander à être contacté')
                        ->live(),
                    CheckboxList::make('callback_channels')
                        ->label('Canaux proposés')
                        ->options(ConversationSetting::CALLBACK_CHANNELS)
                        ->columns(3)
                        ->columnSpanFull(),
                    TextInput::make('notification_email')
                        ->label('E-mail recevant les nouvelles demandes')
                        ->email()
                        ->maxLength(255)
                        ->helperText('À défaut, l’adresse de contact générale du site sera utilisée.'),
                ]),

            Section::make('Instructions particulières')
                ->description('Complément facultatif. Il ne peut pas désactiver les protections universelles.')
                ->schema([
                    Textarea::make('additional_instructions')
                        ->label('Consignes propres à ce site')
                        ->rows(5)
                        ->maxLength(4000),
                ]),

            Section::make('Limites et fonctionnement avancé')
                ->description('Réglages techniques protégés. Les valeurs proposées conviennent à la plupart des sites.')
                ->schema([
                    TextInput::make('max_visitor_messages')
                        ->label('Nombre maximal de messages du visiteur')
                        ->numeric()
                        ->minValue(4)
                        ->maxValue(50)
                        ->required(),
                    TextInput::make('warning_at_message')
                        ->label('Avertir l’assistant à partir du message')
                        ->numeric()
                        ->minValue(2)
                        ->maxValue(49)
                        ->required(),
                    Textarea::make('interaction_limit_message')
                        ->label('Message affiché lorsque la limite est atteinte')
                        ->rows(2)
                        ->maxLength(1000)
                        ->required()
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->collapsed()
                ->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('is_enabled')->label('Actif')->boolean(),
                TextColumn::make('assistant_language')->label('Langue'),
                IconColumn::make('whatsapp_enabled')->label('WhatsApp')->boolean(),
                IconColumn::make('callback_enabled')->label('Contact différé')->boolean(),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Configurer')
                    ->modalWidth(Width::FiveExtraLarge),
            ])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageConversationSettings::route('/'),
        ];
    }
}
