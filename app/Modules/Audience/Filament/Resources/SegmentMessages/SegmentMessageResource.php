<?php

namespace App\Modules\Audience\Filament\Resources\SegmentMessages;

use App\Modules\Audience\Actions\DispatchSegmentMessage;
use App\Modules\Audience\Actions\SendPendingSegmentMessages;
use App\Modules\Audience\Filament\Resources\SegmentMessages\Pages\ManageSegmentMessages;
use App\Modules\Audience\Models\SegmentMessage;
use App\Support\Modules;
use BackedEnum;
use Carbon\CarbonImmutable;
use UnitEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema as SchemaFacade;

class SegmentMessageResource extends Resource
{
    protected static ?string $model = SegmentMessage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaperAirplane;

    protected static ?string $navigationLabel = 'Messages ciblés';

    protected static UnitEnum|string|null $navigationGroup = 'Relation client';

    protected static ?string $modelLabel = 'message ciblé';

    protected static ?string $pluralModelLabel = 'messages ciblés';

    protected static ?int $navigationSort = 40;

    public static function shouldRegisterNavigation(): bool
    {
        return Modules::enabled('audience') && self::hasAudienceTables();
    }

    public static function canAccess(): bool
    {
        return Modules::enabled('audience') && self::hasAudienceTables() && parent::canAccess();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('audience_segment_id')
                    ->label('Segment')
                    ->relationship('segment', 'name')
                    ->required()
                    ->preload(),
                TextInput::make('subject')
                    ->label('Sujet')
                    ->required(),
                Select::make('provider')
                    ->label('Mode d’envoi')
                    ->options([
                        SegmentMessage::PROVIDER_SMTP_LWS => 'Envoi standard du site',
                        SegmentMessage::PROVIDER_BREVO => 'Campagne Brevo',
                    ])
                    ->default(SegmentMessage::PROVIDER_SMTP_LWS)
                    ->required(),
                DateTimePicker::make('scheduled_at')
                    ->label('Date d’envoi souhaitée')
                    ->seconds(false)
                    ->helperText('Optionnel. Si renseigné, Maracuja attendra cette date avant d’envoyer.'),
                RichEditor::make('body')
                    ->label('Message')
                    ->required()
                    ->columnSpanFull(),
                Select::make('status')
                    ->label('Statut')
                    ->options([
                        SegmentMessage::STATUS_DRAFT => 'Brouillon',
                        SegmentMessage::STATUS_READY => 'Prêt',
                        SegmentMessage::STATUS_QUEUED => 'En file',
                        SegmentMessage::STATUS_SYNCING_TO_BREVO => 'Synchronisation Brevo',
                        SegmentMessage::STATUS_SYNC_FAILED => 'Erreur synchronisation',
                        SegmentMessage::STATUS_CREATED_IN_BREVO => 'Créé dans Brevo',
                        SegmentMessage::STATUS_SENDING => 'En cours',
                        SegmentMessage::STATUS_SENT_TO_PROVIDER => 'Envoyé au prestataire',
                        SegmentMessage::STATUS_SENT => 'Terminé',
                        SegmentMessage::STATUS_COMPLETED => 'Campagne terminée',
                        SegmentMessage::STATUS_CANCELLED => 'Annulé',
                        SegmentMessage::STATUS_ARCHIVED => 'Archivé',
                    ])
                    ->default(SegmentMessage::STATUS_DRAFT)
                    ->disabled()
                    ->dehydrated(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('subject')
                    ->label('Sujet')
                    ->searchable(),
                TextColumn::make('segment.name')
                    ->label('Segment'),
                TextColumn::make('provider')
                    ->label('Mode')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        SegmentMessage::PROVIDER_BREVO => 'Campagne Brevo',
                        default => 'Envoi standard',
                    })
                    ->color(fn (?string $state): string => $state === SegmentMessage::PROVIDER_BREVO ? 'info' : 'gray'),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => self::statusLabel($state))
                    ->color(fn (string $state) => self::statusColor($state)),
                TextColumn::make('brevo_campaign_id')
                    ->label('Campagne Brevo')
                    ->formatStateUsing(fn (?int $state): string => $state ? "#{$state}" : '—')
                    ->toggleable(),
                TextColumn::make('targeted_count')
                    ->label('Ciblés')
                    ->state(fn (SegmentMessage $record): int => $record->deliveryReport()['targeted']),
                TextColumn::make('pending_count')
                    ->label('À envoyer')
                    ->state(fn (SegmentMessage $record): int => $record->deliveryReport()['pending']),
                TextColumn::make('accepted_count')
                    ->label('Remis au serveur mail')
                    ->state(fn (SegmentMessage $record): int => $record->deliveryReport()['accepted']),
                TextColumn::make('failed_count')
                    ->label('Refus immédiats')
                    ->state(fn (SegmentMessage $record): int => $record->deliveryReport()['failed']),
                TextColumn::make('excluded_count')
                    ->label('Exclus')
                    ->state(fn (SegmentMessage $record): int => $record->deliveryReport()['excluded']),
                TextColumn::make('sent_at')
                    ->label('Terminé le')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('scheduled_at')
                    ->label('Prévu le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->recordAction('deliveries')
            ->recordActions([
                Action::make('preview')
                    ->label('Aperçu')
                    ->icon(Heroicon::OutlinedEye)
                    ->modalWidth(Width::FourExtraLarge)
                    ->modalHeading(fn (SegmentMessage $record): string => $record->subject)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fermer')
                    ->modalContent(fn (SegmentMessage $record) => view('filament.audience.segment-message-preview', [
                        'segmentMessage' => $record,
                    ])),
                Action::make('sendTest')
                    ->label('Test')
                    ->icon(Heroicon::OutlinedEnvelope)
                    ->form([
                        TextInput::make('email')
                            ->label('Adresse de test')
                            ->email()
                            ->required(),
                    ])
                    ->action(function (SegmentMessage $record, array $data): void {
                        $contact = new \App\Modules\Audience\Models\AudienceContact([
                            'email' => $data['email'],
                            'accepts_email' => true,
                        ]);

                        Mail::to($data['email'])->send(new \App\Modules\Audience\Mail\SegmentMessageMail($record, $contact));

                        Notification::make()
                            ->title('Email de test envoyé')
                            ->body("Un aperçu a été envoyé à {$data['email']}.")
                            ->success()
                            ->send();
                    }),
                Action::make('send')
                    ->label('Envoyer')
                    ->icon(Heroicon::OutlinedPaperAirplane)
                    ->form([
                        DateTimePicker::make('scheduled_at')
                            ->label('Date d’envoi')
                            ->seconds(false)
                            ->helperText('Laissez vide pour envoyer maintenant.')
                            ->default(fn (SegmentMessage $record) => $record->scheduled_at),
                    ])
                    ->modalHeading('Envoyer ce message')
                    ->modalDescription(fn (SegmentMessage $record): string => self::sendModalDescription($record))
                    ->modalSubmitActionLabel('Valider')
                    ->visible(fn (SegmentMessage $record): bool => ! in_array($record->status, [
                        SegmentMessage::STATUS_SENT_TO_PROVIDER,
                        SegmentMessage::STATUS_SENT,
                        SegmentMessage::STATUS_COMPLETED,
                        SegmentMessage::STATUS_CANCELLED,
                        SegmentMessage::STATUS_ARCHIVED,
                    ], true))
                    ->action(function (SegmentMessage $record, array $data): void {
                        $scheduledAt = filled($data['scheduled_at'] ?? null)
                            ? CarbonImmutable::parse($data['scheduled_at'])
                            : null;

                        try {
                            $stats = DispatchSegmentMessage::run($record, $scheduledAt);
                        } catch (\Throwable $exception) {
                            Notification::make()
                                ->title('Envoi impossible')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();

                            return;
                        }

                        if ($stats['queued'] === 0 && $stats['sent'] === 0) {
                            Notification::make()
                                ->title('Aucun destinataire éligible')
                                ->body('Vérifiez le segment et les préférences email des contacts.')
                                ->warning()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title($stats['scheduled'] ? 'Message planifié' : 'Envoi lancé')
                            ->body(self::sendNotificationBody($stats))
                            ->success()
                            ->send();
                    }),
                Action::make('processBatch')
                    ->label('Traiter un lot')
                    ->icon(Heroicon::OutlinedPlay)
                    ->requiresConfirmation()
                    ->modalDescription('Envoie les prochains messages à envoyer ou retente les refus immédiats de cette campagne uniquement.')
                    ->visible(fn (SegmentMessage $record): bool => $record->isQueuedOrSending() && $record->hasProcessableDeliveries())
                    ->action(function (SegmentMessage $record): void {
                        $stats = SendPendingSegmentMessages::runForMessage($record, limit: 25);

                        Notification::make()
                            ->title('Lot traité')
                            ->body("{$stats['sent']} remis au serveur mail, {$stats['failed']} refus immédiat(s), {$stats['skipped']} exclu(s).")
                            ->success()
                            ->send();
                    }),
                Action::make('deliveries')
                    ->label('Rapport')
                    ->icon(Heroicon::OutlinedListBullet)
                    ->modalWidth(Width::SevenExtraLarge)
                    ->modalHeading(fn (SegmentMessage $record): string => 'Rapport - ' . $record->subject)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fermer')
                    ->modalContent(fn (SegmentMessage $record) => view('filament.audience.segment-message-deliveries', [
                        'segmentMessage' => $record,
                        'deliveries' => $record->deliveries()
                            ->with('contact')
                            ->orderBy('email')
                            ->get(),
                    ])),
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
            'index' => ManageSegmentMessages::route('/'),
        ];
    }

    private static function hasAudienceTables(): bool
    {
        return SchemaFacade::hasTable('segment_messages') && SchemaFacade::hasTable('audience_segments');
    }

    private static function eligibleRecipientsCount(SegmentMessage $message): int
    {
        return $message->segment
            ->contacts()
            ->where('accepts_email', true)
            ->whereNull('unsubscribed_at')
            ->whereNull('hard_bounced_at')
            ->whereNull('email_blacklisted_at')
            ->count();
    }

    private static function sendModalDescription(SegmentMessage $message): string
    {
        $eligible = self::eligibleRecipientsCount($message);

        return $eligible === 0
            ? 'Aucun destinataire éligible pour ce segment.'
            : "Cet envoi concerne {$eligible} destinataire(s) éligible(s).";
    }

    private static function sendNotificationBody(array $stats): string
    {
        if ($stats['scheduled']) {
            return "{$stats['queued']} destinataire(s) éligible(s). Maracuja enverra à la date choisie.";
        }

        if ($stats['processed'] === 1 && $stats['sent'] > 0) {
            return "{$stats['sent']} destinataire(s) confié(s) au canal d’envoi.";
        }

        return "{$stats['sent']} envoyé(s), {$stats['failed']} échec(s), {$stats['skipped']} ignoré(s).";
    }

    private static function statusLabel(string $state): string
    {
        return match ($state) {
            SegmentMessage::STATUS_SENT => 'Terminé',
            SegmentMessage::STATUS_READY => 'Prêt',
            SegmentMessage::STATUS_QUEUED => 'En file',
            SegmentMessage::STATUS_SYNCING_TO_BREVO => 'Synchronisation Brevo',
            SegmentMessage::STATUS_SYNC_FAILED => 'Erreur synchronisation',
            SegmentMessage::STATUS_CREATED_IN_BREVO => 'Créé dans Brevo',
            SegmentMessage::STATUS_SENDING => 'En cours',
            SegmentMessage::STATUS_SENT_TO_PROVIDER => 'Envoyé au prestataire',
            SegmentMessage::STATUS_CANCELLED => 'Annulé',
            SegmentMessage::STATUS_COMPLETED => 'Campagne terminée',
            SegmentMessage::STATUS_ARCHIVED => 'Archivé',
            default => 'Brouillon',
        };
    }

    private static function statusColor(string $state): string
    {
        return match ($state) {
            SegmentMessage::STATUS_SENT => 'success',
            SegmentMessage::STATUS_COMPLETED, SegmentMessage::STATUS_CREATED_IN_BREVO => 'success',
            SegmentMessage::STATUS_QUEUED,
            SegmentMessage::STATUS_SENDING,
            SegmentMessage::STATUS_SYNCING_TO_BREVO,
            SegmentMessage::STATUS_SENT_TO_PROVIDER => 'warning',
            SegmentMessage::STATUS_CANCELLED, SegmentMessage::STATUS_SYNC_FAILED => 'danger',
            SegmentMessage::STATUS_ARCHIVED => 'gray',
            default => 'gray',
        };
    }
}
