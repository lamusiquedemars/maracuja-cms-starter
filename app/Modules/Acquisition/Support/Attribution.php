<?php

namespace App\Modules\Acquisition\Support;

class Attribution
{
    private const TOUCH_FIELDS = [
        'utm_source' => 255,
        'utm_medium' => 255,
        'utm_campaign' => 255,
        'utm_term' => 255,
        'utm_content' => 255,
        'gclid' => 255,
        'gbraid' => 255,
        'wbraid' => 255,
        'landing_page' => 2048,
        'referrer' => 2048,
        'captured_at' => 64,
    ];

    /** @return array{first_touch: ?array<string, string>, last_touch: ?array<string, string>, source: ?string, medium: ?string, campaign: ?string, method: string, confidence: float} */
    public static function fromJson(?string $json): array
    {
        $decoded = json_decode((string) $json, true);
        $decoded = is_array($decoded) ? $decoded : [];
        $first = self::cleanTouch(is_array($decoded['first_touch'] ?? null) ? $decoded['first_touch'] : null);
        $last = self::cleanTouch(is_array($decoded['last_touch'] ?? null) ? $decoded['last_touch'] : null);
        $source = $last['utm_source'] ?? $first['utm_source'] ?? null;
        $medium = $last['utm_medium'] ?? $first['utm_medium'] ?? null;
        $campaign = $last['utm_campaign'] ?? $first['utm_campaign'] ?? null;
        $hasClickId = filled($last['gclid'] ?? $first['gclid'] ?? null)
            || filled($last['gbraid'] ?? $first['gbraid'] ?? null)
            || filled($last['wbraid'] ?? $first['wbraid'] ?? null);

        if ($source === null && $hasClickId) {
            $source = 'google';
            $medium ??= 'cpc';
        }

        return [
            'first_touch' => $first,
            'last_touch' => $last,
            'source' => $source,
            'medium' => $medium,
            'campaign' => $campaign,
            'method' => 'first_party',
            'confidence' => $hasClickId || $source !== null ? 1.0 : 0.0,
        ];
    }

    /** @param array<string, mixed>|null $touch
     *  @return array<string, string>|null */
    private static function cleanTouch(?array $touch): ?array
    {
        if ($touch === null) {
            return null;
        }

        $cleaned = [];

        foreach (self::TOUCH_FIELDS as $field => $maximumLength) {
            $value = $touch[$field] ?? null;

            if (! is_scalar($value)) {
                continue;
            }

            $value = trim((string) $value);

            if ($value !== '') {
                $cleaned[$field] = mb_substr($value, 0, $maximumLength);
            }
        }

        return $cleaned !== [] ? $cleaned : null;
    }
}
