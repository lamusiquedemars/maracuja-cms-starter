<?php

namespace App\Modules\Conversations\Console\Commands;

use App\Modules\Conversations\Enums\ConversationStatus;
use App\Modules\Conversations\Models\Conversation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PruneConversationsCommand extends Command
{
    protected $signature = 'conversations:prune {--dry-run : Count records without deleting them}';

    protected $description = 'Archive abandoned conversations and delete threads past the retention period';

    public function handle(): int
    {
        $days = max(1, (int) config('maracuja.conversations.retention_days', 90));
        $inactiveHours = max(1, (int) config('maracuja.conversations.archive_inactive_after_hours', 48));
        $archiveQuery = Conversation::query()
            ->whereIn('status', [
                ConversationStatus::New,
                ConversationStatus::AiActive,
                ConversationStatus::WaitingForVisitor,
            ])
            ->whereDoesntHave('inquiry')
            ->where('last_message_at', '<', now()->subHours($inactiveHours));
        $archiveCount = (clone $archiveQuery)->count();
        $deleteQuery = Conversation::query()
            ->whereIn('status', [
                ConversationStatus::Closed,
                ConversationStatus::Archived,
            ])
            ->where('updated_at', '<', now()->subDays($days));
        $deleteCount = (clone $deleteQuery)->count();

        if (! $this->option('dry-run')) {
            $archiveQuery->eachById(fn (Conversation $conversation) => $conversation->update([
                'status' => ConversationStatus::Archived,
                'ai_enabled' => false,
                'closed_at' => now(),
            ]));
            $deleteQuery->eachById(fn (Conversation $conversation) => $conversation->delete());
        }

        Log::info('Conversation retention completed.', [
            'archive_count' => $archiveCount,
            'delete_count' => $deleteCount,
            'archive_inactive_after_hours' => $inactiveHours,
            'retention_days' => $days,
            'dry_run' => (bool) $this->option('dry-run'),
        ]);

        $this->info(
            "{$archiveCount} conversation(s) à archiver, {$deleteCount} à supprimer"
            .($this->option('dry-run') ? ' (simulation).' : '.'),
        );

        return self::SUCCESS;
    }
}
