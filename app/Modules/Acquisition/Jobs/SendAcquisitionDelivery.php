<?php

namespace App\Modules\Acquisition\Jobs;

use App\Modules\Acquisition\Models\AcquisitionDelivery;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class SendAcquisitionDelivery implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public array $backoff = [60, 300, 900, 3600];

    public function __construct(public readonly int $deliveryId) {}

    public function handle(): void
    {
        $delivery = AcquisitionDelivery::query()->findOrFail($this->deliveryId);
        if ($delivery->status === 'sent') {
            return;
        }
        $delivery->update(['status' => 'sending', 'attempts' => $delivery->attempts + 1, 'last_attempt_at' => now(), 'last_error' => null]);
        try {
            $response = Http::acceptJson()->asJson()->withToken((string) config('maracuja.acquisition.cremona.token'))->withHeader('Idempotency-Key', $delivery->idempotency_key)->timeout(15)->post((string) config('maracuja.acquisition.cremona.endpoint'), $delivery->payload);
            if (in_array($response->status(), [401, 403, 409, 422], true)) {
                $delivery->update(['status' => 'failed', 'response_status' => $response->status(), 'last_error' => "Cremona returned HTTP {$response->status()}."]);

                return;
            }
            if (! $response->successful()) {
                throw new RuntimeException("Cremona returned HTTP {$response->status()}.");
            }
            $delivery->update(['status' => 'sent', 'response_status' => $response->status(), 'sent_at' => now(), 'last_error' => null]);
        } catch (Throwable $exception) {
            $delivery->update(['status' => 'pending', 'last_error' => mb_substr($exception->getMessage(), 0, 2000)]);
            throw $exception;
        }
    }

    public function failed(Throwable $exception): void
    {
        AcquisitionDelivery::query()->whereKey($this->deliveryId)->update(['status' => 'failed', 'last_error' => mb_substr($exception->getMessage(), 0, 2000)]);
    }
}
