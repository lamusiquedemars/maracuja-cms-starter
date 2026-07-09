<?php

namespace App\Modules\Audience\Services;

use App\Modules\Audience\Models\AudienceBrevoSetting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class BrevoAudienceService
{
    private const BASE_URL = 'https://api.brevo.com/v3';

    /**
     * @return array{ok: bool, status: string, message: string}
     */
    public function testConnection(AudienceBrevoSetting $setting): array
    {
        if (! $setting->hasApiKey()) {
            return [
                'ok' => false,
                'status' => AudienceBrevoSetting::TEST_STATUS_MISSING_KEY,
                'message' => 'Aucune clé API Brevo n’est enregistrée.',
            ];
        }

        try {
            $response = Http::withHeaders([
                'api-key' => (string) $setting->api_key_encrypted,
                'accept' => 'application/json',
            ])
                ->timeout(10)
                ->get(self::BASE_URL . '/account');
        } catch (ConnectionException $exception) {
            return [
                'ok' => false,
                'status' => AudienceBrevoSetting::TEST_STATUS_FAILED,
                'message' => 'Connexion Brevo impossible: ' . $exception->getMessage(),
            ];
        }

        if ($response->successful()) {
            return [
                'ok' => true,
                'status' => AudienceBrevoSetting::TEST_STATUS_SUCCESS,
                'message' => 'Connexion Brevo validée.',
            ];
        }

        return [
            'ok' => false,
            'status' => AudienceBrevoSetting::TEST_STATUS_FAILED,
            'message' => 'Brevo a refuse la connexion (' . $response->status() . ').',
        ];
    }
}
