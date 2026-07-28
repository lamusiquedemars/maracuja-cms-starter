<?php

namespace App\Modules\Conversations\Console\Commands;

use App\Modules\Conversations\Enums\ConversationStatus;
use App\Modules\Conversations\Models\Conversation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PruneConversationsCommand extends Command
{
    protected $signature = 'conversations:prune {--dry-run : Count records without deleting them}';

    protected $description = 'Delete closed conversation threads past the configured retention period';

    public function handle(): int
    {
        $days = max(1, (int) config('maracuja.conversations.retention_days', 90));
        $query = Conversation::query()
            ->whereIn('status', [
                ConversationStatus::Closed,
                ConversationStatus::Archived,
            ])
            ->where('updated_at', '<', now()->subDays($days));
        $count = (clone $query)->count();

        if (! $this->option('dry-run')) {
            $query->eachById(fn (Conversation $conversation) => $conversation->delete());
        }

        Log::info('Conversation retention completed.', [
            'count' => $count,
            'retention_days' => $days,
            'dry_run' => (bool) $this->option('dry-run'),
        ]);

        $this->info("{$count} conversation(s) ".($this->option('dry-run') ? 'à supprimer.' : 'supprimée(s).'));

        return self::SUCCESS;
    }
}
