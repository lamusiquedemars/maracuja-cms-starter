<?php

namespace App\Modules\Conversations\Filament\Resources\Conversations;

use App\Modules\Conversations\Enums\ConversationStatus;
use App\Modules\Conversations\Enums\ConversationUrgency;
use App\Modules\Conversations\Filament\Resources\Conversations\Pages\ListConversations;
use App\Modules\Conversations\Filament\Resources\Conversations\Pages\ViewConversation;
use App\Modules\Conversations\Models\Conversation;
use App\Support\Modules;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ConversationResource extends Resource
{
    protected static ?string $model = Conversation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $navigationLabel = 'Conversations';

    protected static UnitEnum|string|null $navigationGroup = 'Accueil';

    protected static ?string $modelLabel = 'conversation';

    protected static ?string $pluralModelLabel = 'conversations';

    protected static ?int $navigationSort = 5;

    public static function getNavigationLabel(): string { return __('admin.conversations.navigation'); }
    public static function getNavigationGroup(): ?string { return __('admin.conversations.group'); }
    public static function getModelLabel(): string { return __('admin.conversations.singular'); }
    public static function getPluralModelLabel(): string { return __('admin.conversations.plural'); }

    public static function shouldRegisterNavigation(): bool
    {
        return Modules::enabled('conversations');
    }

    public static function canAccess(): bool
    {
        return Modules::enabled('conversations') && parent::canAccess();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with(['contact', 'messages'])
                ->latest('last_message_at'))
            ->columns([
                TextColumn::make('public_reference')
                    ->label(__('admin.conversations.reference'))
                    ->searchable(),
                TextColumn::make('contact.display_name')
                    ->label(__('admin.conversations.contact'))
                    ->default(__('admin.conversations.anonymous_visitor'))
                    ->searchable(),
                TextColumn::make('status')
                    ->label(__('admin.conversations.status'))
                    ->badge()
                    ->formatStateUsing(fn (ConversationStatus $state): string => $state->label())
                    ->color(fn (ConversationStatus $state): string => $state->color()),
                TextColumn::make('urgency')
                    ->label(__('admin.conversations.urgency'))
                    ->badge()
                    ->formatStateUsing(fn (ConversationUrgency $state): string => $state->label())
                    ->color(fn (ConversationUrgency $state): string => $state->color()),
                TextColumn::make('topic')->label(__('admin.conversations.subject'))->limit(32)->toggleable(),
                TextColumn::make('messages.content')
                    ->label(__('admin.conversations.last_message'))
                    ->getStateUsing(fn (Conversation $record): ?string => $record->messages
                        ->sortByDesc('sent_at')
                        ->first()
                        ?->content)
                    ->limit(48),
                TextColumn::make('last_message_at')
                    ->label(__('admin.conversations.last_interaction'))
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('admin.conversations.status'))
                    ->options(collect(ConversationStatus::cases())
                        ->mapWithKeys(fn (ConversationStatus $status): array => [$status->value => $status->label()])
                        ->all()),
                SelectFilter::make('urgency')
                    ->label(__('admin.conversations.urgency'))
                    ->options(collect(ConversationUrgency::cases())
                        ->mapWithKeys(fn (ConversationUrgency $urgency): array => [$urgency->value => $urgency->label()])
                        ->all()),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->recordUrl(fn (Conversation $record): string => static::getUrl('view', ['record' => $record]));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListConversations::route('/'),
            'view' => ViewConversation::route('/{record}'),
        ];
    }
}
