<?php

namespace App\Modules\Audience\Filament\Resources\SegmentMessages;

use App\Modules\Audience\Actions\QueueSegmentMessage;
use App\Modules\Audience\Actions\SendPendingSegmentMessages;
use App\Modules\Audience\Filament\Resources\SegmentMessages\Pages\ManageSegmentMessages;
use App\Modules\Audience\Models\SegmentMessage;
use App\Support\Modules;
use BackedEnum;
use UnitEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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
                RichEditor::make('body')
                    ->label('Message')
                    ->required()
                    ->columnSpanFull(),
                Select::make('status')
                    ->label('Statut')
                    ->options([
                        SegmentMessage::STATUS_DRAFT => 'Brouillon',
                        SegmentMessage::STATUS_QUEUED => 'En file',
                        SegmentMessage::STATUS_SENDING => 'En cours',
                        SegmentMessage::STATUS_SENT => 'Terminé',
                        SegmentMessage::STATUS_CANCELLED => 'Annulé',
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
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => self::statusLabel($state))
                    ->color(fn (string $state) => self::statusColor($state)),
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
                    ->label('Planifier')
                    ->icon(Heroicon::OutlinedPaperAirplane)
                    ->requiresConfirmation()
                    ->modalDescription(fn (SegmentMessage $record): string => self::sendModalDescription($record))
                    ->visible(fn (SegmentMessage $record): bool => $record->isDraft())
                    ->action(function (SegmentMessage $record): void {
                        $queuedCount = QueueSegmentMessage::run($record);

                        if ($queuedCount === 0) {
                            Notification::make()
                                ->title('Aucun destinataire éligible')
                                ->body('Vérifiez le segment et les préférences email des contacts.')
                                ->warning()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Message planifié')
                            ->body("{$queuedCount} destinataire(s) en file. Tâche planifiée conseillée: 25 envois toutes les 15 minutes (100/h).")
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
            ->count();
    }

    private static function sendModalDescription(SegmentMessage $message): string
    {
        $eligible = self::eligibleRecipientsCount($message);

        return $eligible === 0
            ? 'Aucun destinataire éligible pour ce segment.'
            : "Cet envoi sera mis en file pour {$eligible} destinataire(s) éligible(s). La tâche planifiée enverra ensuite par lots.";
    }

    private static function statusLabel(string $state): string
    {
        return match ($state) {
            SegmentMessage::STATUS_SENT => 'Terminé',
            SegmentMessage::STATUS_QUEUED => 'En file',
            SegmentMessage::STATUS_SENDING => 'En cours',
            SegmentMessage::STATUS_CANCELLED => 'Annulé',
            default => 'Brouillon',
        };
    }

    private static function statusColor(string $state): string
    {
        return match ($state) {
            SegmentMessage::STATUS_SENT => 'success',
            SegmentMessage::STATUS_QUEUED, SegmentMessage::STATUS_SENDING => 'warning',
            SegmentMessage::STATUS_CANCELLED => 'danger',
            default => 'gray',
        };
    }
}
