<?php

namespace App\Modules\Inquiries\Filament\Resources\Inquiries;

use App\Modules\Audience\Actions\CreateContactFromInquiry;
use App\Modules\Inquiries\Enums\InquiryStatus;
use App\Modules\Inquiries\Filament\Resources\Inquiries\Pages\ManageInquiries;
use App\Modules\Inquiries\Models\Inquiry;
use App\Modules\Inquiries\Support\InquiryReplyLink;
use App\Support\Modules;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema as SchemaFacade;
use UnitEnum;

class InquiryResource extends Resource
{
    protected static ?string $model = Inquiry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInbox;

    protected static ?string $navigationLabel = 'Demandes reçues';

    protected static UnitEnum|string|null $navigationGroup = 'Relation client';

    protected static ?string $modelLabel = 'demande';

    protected static ?string $pluralModelLabel = 'demandes';

    protected static ?int $navigationSort = 10;

    public static function shouldRegisterNavigation(): bool
    {
        return Modules::enabled('inquiries') && self::hasInquiriesTable();
    }

    public static function canAccess(): bool
    {
        return Modules::enabled('inquiries') && self::hasInquiriesTable() && parent::canAccess();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nom')
                    ->required(),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required(),
                TextInput::make('phone')
                    ->label('Téléphone')
                    ->tel(),
                TextInput::make('subject')
                    ->label('Sujet'),
                Select::make('status')
                    ->label('Statut')
                    ->options(self::statusOptions())
                    ->required(),
                Textarea::make('message')
                    ->label('Message')
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('internal_notes')
                    ->label('Notes internes')
                    ->columnSpanFull(),
                DateTimePicker::make('read_at')
                    ->label('Consulté le'),
                DateTimePicker::make('handled_at')
                    ->label('Traité le'),
                DateTimePicker::make('archived_at')
                    ->label('Archivé le'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->orderByRaw(
                    'case when status = ? then 0 when status = ? then 1 else 2 end',
                    [InquiryStatus::New->value, InquiryStatus::ToHandle->value]
                )
                ->latest()
            )
            ->columns([
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn (InquiryStatus|string|null $state) => self::statusFrom($state)?->label() ?? '-')
                    ->color(fn (InquiryStatus|string|null $state) => self::statusFrom($state)?->color() ?? 'gray')
                    ->sortable(),
                TextColumn::make('conversation.public_reference')
                    ->label('Conversation')
                    ->placeholder('—')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('subject')
                    ->label('Sujet')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Reçu le')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('read_at')
                    ->label('Consulté le')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('quick_view')
                    ->label('Vue rapide')
                    ->options([
                        'all' => 'Tous',
                        'priority' => 'Prioritaires',
                        'waiting_customer' => 'En attente client',
                        'closed' => 'Traitées / Archivées',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? 'all') {
                            'waiting_customer' => $query->where('status', InquiryStatus::WaitingCustomer->value),
                            'closed' => $query->whereIn('status', [
                                InquiryStatus::Handled->value,
                                InquiryStatus::Archived->value,
                            ]),
                            'priority' => $query->whereIn('status', [
                                InquiryStatus::New->value,
                                InquiryStatus::ToHandle->value,
                            ]),
                            default => $query,
                        };
                    }),
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options(self::statusOptions()),
            ])
            ->recordActions([
                Action::make('reply')
                    ->label('Répondre')
                    ->icon(Heroicon::OutlinedEnvelope)
                    ->requiresConfirmation()
                    ->modalHeading('Ouvrir votre messagerie ?')
                    ->modalDescription('La demande passera en "En attente client".')
                    ->action(function (Inquiry $record) {
                        $record->markWaitingCustomer();

                        return redirect(self::mailtoUrl($record));
                    }),
                Action::make('createContact')
                    ->label('Créer contact')
                    ->icon(Heroicon::OutlinedUserPlus)
                    ->visible(fn (Inquiry $record): bool => self::canCreateAudienceContact() && filled($record->email))
                    ->action(function (Inquiry $record): void {
                        $result = CreateContactFromInquiry::run($record);

                        Notification::make()
                            ->title($result['created'] ? 'Contact créé' : 'Contact déjà existant')
                            ->body($result['contact']->email)
                            ->success()
                            ->send();
                    }),
                ActionGroup::make([
                    Action::make('markRead')
                        ->label('Consulté')
                        ->icon(Heroicon::OutlinedEye)
                        ->visible(fn (Inquiry $record): bool => $record->read_at === null)
                        ->action(fn (Inquiry $record) => $record->markRead()),
                    Action::make('markToHandle')
                        ->label('À traiter')
                        ->icon(Heroicon::OutlinedExclamationCircle)
                        ->visible(fn (Inquiry $record): bool => $record->status !== InquiryStatus::ToHandle)
                        ->action(fn (Inquiry $record) => $record->moveTo(InquiryStatus::ToHandle)),
                    Action::make('markWaitingCustomer')
                        ->label('En attente client')
                        ->icon(Heroicon::OutlinedClock)
                        ->visible(fn (Inquiry $record): bool => $record->status !== InquiryStatus::WaitingCustomer)
                        ->action(fn (Inquiry $record) => $record->moveTo(InquiryStatus::WaitingCustomer)),
                    Action::make('markHandled')
                        ->label('Traité')
                        ->icon(Heroicon::OutlinedCheckCircle)
                        ->visible(fn (Inquiry $record): bool => $record->status !== InquiryStatus::Handled)
                        ->action(fn (Inquiry $record) => $record->moveTo(InquiryStatus::Handled)),
                    Action::make('archive')
                        ->label('Archiver')
                        ->icon(Heroicon::OutlinedArchiveBox)
                        ->visible(fn (Inquiry $record): bool => $record->status !== InquiryStatus::Archived)
                        ->action(fn (Inquiry $record) => $record->moveTo(InquiryStatus::Archived)),
                ])
                    ->label('Suivi')
                    ->icon(Heroicon::OutlinedEllipsisVertical),
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
            'index' => ManageInquiries::route('/'),
        ];
    }

    private static function statusOptions(): array
    {
        return collect(InquiryStatus::cases())
            ->mapWithKeys(fn (InquiryStatus $status) => [$status->value => $status->label()])
            ->all();
    }

    private static function mailtoUrl(Inquiry $record): string
    {
        return InquiryReplyLink::make($record);
    }

    private static function statusFrom(InquiryStatus|string|null $status): ?InquiryStatus
    {
        if ($status instanceof InquiryStatus) {
            return $status;
        }

        if (is_string($status)) {
            return InquiryStatus::tryFrom($status);
        }

        return null;
    }

    private static function hasInquiriesTable(): bool
    {
        return SchemaFacade::hasTable('inquiries');
    }

    private static function canCreateAudienceContact(): bool
    {
        return Modules::enabled('audience')
            && class_exists(CreateContactFromInquiry::class)
            && SchemaFacade::hasTable('audience_contacts');
    }
}
