<?php

namespace App\Modules\Acquisition\Services;

use Illuminate\Support\Facades\Http;

class CremonaReportingClient
{
    /** @return array<string, mixed> */
    public function summary(string $siteReference, int $days = 30): array
    {
        $response = Http::acceptJson()->withToken((string) config('maracuja.acquisition.cremona.token'))
            ->timeout(15)->get((string) config('maracuja.acquisition.cremona.reporting_endpoint'), [
                'site_reference' => $siteReference, 'days' => $days,
            ]);

        $response->throw();

        return $response->json('data') ?? [];
    }
}
