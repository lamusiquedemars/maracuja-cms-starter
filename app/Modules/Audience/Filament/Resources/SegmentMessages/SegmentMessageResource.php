<?php

namespace App\Modules\Audience\Filament\Resources\SegmentMessages;

use App\Modules\Audience\Actions\SendSegmentMessage;
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
                        'draft' => 'Brouillon',
                        'sent' => 'Envoyé',
                    ])
                    ->default('draft')
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
                    ->formatStateUsing(fn (string $state) => $state === 'sent' ? 'Envoyé' : 'Brouillon')
                    ->color(fn (string $state) => $state === 'sent' ? 'success' : 'gray'),
                TextColumn::make('recipients_count')
                    ->label('Envoyés'),
                TextColumn::make('eligible_recipients')
                    ->label('Éligibles')
                    ->state(fn (SegmentMessage $record): int => self::eligibleRecipientsCount($record)),
                TextColumn::make('delivered_count')
                    ->label('Livrés')
                    ->state(fn (SegmentMessage $record): int => $record->deliveries()->where('status', 'sent')->count()),
                TextColumn::make('failed_count')
                    ->label('Échecs')
                    ->state(fn (SegmentMessage $record): int => $record->deliveries()->where('status', 'failed')->count()),
                TextColumn::make('sent_at')
                    ->label('Envoyé le')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('preview')
                    ->label('Aperçu')
                    ->icon(Heroicon::OutlinedEye)
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
                    ->requiresConfirmation()
                    ->modalDescription(fn (SegmentMessage $record): string => self::sendModalDescription($record))
                    ->visible(fn (SegmentMessage $record): bool => ! $record->isSent())
                    ->action(function (SegmentMessage $record): void {
                        $sentCount = SendSegmentMessage::run($record);

                        if ($sentCount === 0) {
                            Notification::make()
                                ->title('Aucun destinataire éligible')
                                ->body('Vérifiez le segment et les préférences email des contacts.')
                                ->warning()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Message envoyé')
                            ->body("{$sentCount} destinataire(s) contacté(s).")
                            ->success()
                            ->send();
                    }),
                Action::make('deliveries')
                    ->label('Livraisons')
                    ->icon(Heroicon::OutlinedListBullet)
                    ->modalHeading('Détail des livraisons')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fermer')
                    ->modalContent(fn (SegmentMessage $record) => view('filament.audience.segment-message-deliveries', [
                        'deliveries' => $record->deliveries()->latest()->get(),
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
            : "Cet envoi partira vers {$eligible} destinataire(s) éligible(s).";
    }
}
