<?php

namespace App\Modules\Conversations\Filament\Resources\Conversations\Pages;

use App\Modules\Conversations\Actions\AddMessage;
use App\Modules\Conversations\Enums\ConversationStatus;
use App\Modules\Conversations\Enums\MessageAuthorType;
use App\Modules\Conversations\Enums\MessageVisibility;
use App\Modules\Conversations\Enums\HandoverReason;
use App\Modules\Conversations\Filament\Resources\Conversations\ConversationResource;
use App\Modules\Conversations\Support\WhatsAppHandoverLink;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewConversation extends ViewRecord
{
    protected static string $resource = ConversationResource::class;

    protected string $view = 'filament.conversations.view';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('takeOver')
                ->label('Prendre en charge')
                ->visible(fn (): bool => ! in_array($this->record->status, [
                    ConversationStatus::HumanActive,
                    ConversationStatus::Closed,
                    ConversationStatus::Archived,
                ], true))
                ->action(function (): void {
                    $this->record->update([
                        'assigned_user_id' => auth()->id(),
                        'status' => ConversationStatus::HumanActive,
                        'ai_enabled' => false,
                        'human_handover_at' => $this->record->human_handover_at ?? now(),
                        'handover_reason' => $this->record->handover_reason ?? HandoverReason::Manual,
                    ]);

                    Notification::make()->title('Conversation prise en charge')->success()->send();
                }),
            Action::make('internalNote')
                ->label('Ajouter une note interne')
                ->schema([
                    Textarea::make('content')
                        ->label('Note interne')
                        ->required()
                        ->maxLength(5000),
                ])
                ->action(function (array $data): void {
                    AddMessage::run(
                        $this->record,
                        $data['content'],
                        MessageAuthorType::Human,
                        MessageVisibility::Internal,
                        auth()->user(),
                    );

                    Notification::make()->title('Note interne ajoutée')->success()->send();
                }),
            Action::make('whatsapp')
                ->label('Contacter sur WhatsApp')
                ->url(fn (): ?string => WhatsAppHandoverLink::makeForContact($this->record))
                ->openUrlInNewTab()
                ->visible(fn (): bool => WhatsAppHandoverLink::makeForContact($this->record) !== null),
            Action::make('close')
                ->label('Clôturer')
                ->color('gray')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->status !== ConversationStatus::Closed)
                ->action(function (): void {
                    $this->record->update([
                        'status' => ConversationStatus::Closed,
                        'ai_enabled' => false,
                        'closed_at' => now(),
                    ]);

                    Notification::make()->title('Conversation clôturée')->success()->send();
                }),
        ];
    }
}
