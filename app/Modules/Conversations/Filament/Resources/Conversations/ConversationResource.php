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
                    ->label('Référence')
                    ->searchable(),
                TextColumn::make('contact.display_name')
                    ->label('Contact')
                    ->default('Visiteur anonyme')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn (ConversationStatus $state): string => $state->label())
                    ->color(fn (ConversationStatus $state): string => $state->color()),
                TextColumn::make('urgency')
                    ->label('Urgence')
                    ->badge()
                    ->formatStateUsing(fn (ConversationUrgency $state): string => $state->label())
                    ->color(fn (ConversationUrgency $state): string => $state->color()),
                TextColumn::make('topic')->label('Sujet')->limit(32)->toggleable(),
                TextColumn::make('messages.content')
                    ->label('Dernier message')
                    ->getStateUsing(fn (Conversation $record): ?string => $record->messages
                        ->sortByDesc('sent_at')
                        ->first()
                        ?->content)
                    ->limit(48),
                TextColumn::make('last_message_at')
                    ->label('Dernier échange')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options(collect(ConversationStatus::cases())
                        ->mapWithKeys(fn (ConversationStatus $status): array => [$status->value => $status->label()])
                        ->all()),
                SelectFilter::make('urgency')
                    ->label('Urgence')
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
