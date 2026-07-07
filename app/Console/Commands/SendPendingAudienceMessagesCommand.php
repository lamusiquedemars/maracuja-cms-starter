<?php

namespace App\Console\Commands;

use App\Modules\Audience\Actions\SendPendingSegmentMessages;
use App\Support\Modules;
use Illuminate\Console\Command;

class SendPendingAudienceMessagesCommand extends Command
{
    protected $signature = 'audience:send-pending
        {--limit=25 : Nombre maximum de messages à envoyer pendant ce passage}
        {--max-seconds=180 : Durée maximum du passage avant arrêt propre}
        {--max-attempts=3 : Nombre maximum de tentatives par destinataire}';

    protected $description = 'Envoie progressivement les messages ciblés en attente.';

    public function handle(): int
    {
        if (! Modules::enabled('audience')) {
            $this->warn('Le module Audience est désactivé.');

            return self::SUCCESS;
        }

        $stats = SendPendingSegmentMessages::run(
            limit: (int) $this->option('limit'),
            maxSeconds: (int) $this->option('max-seconds'),
            maxAttempts: (int) $this->option('max-attempts'),
        );

        $this->info("Envoyés: {$stats['sent']} | Échecs: {$stats['failed']} | Ignorés: {$stats['skipped']} | Traités: {$stats['processed']}");

        return self::SUCCESS;
    }
}
