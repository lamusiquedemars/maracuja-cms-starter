<?php

namespace App\Console\Commands;

use App\Modules\Acquisition\Jobs\SendAcquisitionDelivery;
use App\Modules\Acquisition\Models\AcquisitionDelivery;
use Illuminate\Console\Command;

class RetryAcquisitionDeliveriesCommand extends Command
{
    protected $signature = 'acquisition:retry-deliveries {--limit=50 : Nombre maximal de transmissions à relancer}';

    protected $description = 'Relance les transmissions de demandes en attente vers Cremona.';

    public function handle(): int
    {
        $deliveries = AcquisitionDelivery::query()
            ->where('status', 'pending')
            ->orderBy('last_attempt_at')
            ->limit((int) $this->option('limit'))
            ->get();

        foreach ($deliveries as $delivery) {
            (new SendAcquisitionDelivery($delivery->getKey()))->handle();
        }

        $this->components->info("{$deliveries->count()} transmission(s) relancée(s).");

        return self::SUCCESS;
    }
}
