<?php

namespace App\Modules\Audience\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AudienceBrevoSetting extends Model
{
    public const TEST_STATUS_SUCCESS = 'success';
    public const TEST_STATUS_FAILED = 'failed';
    public const TEST_STATUS_MISSING_KEY = 'missing_key';

    protected $fillable = [
        'is_enabled',
        'api_key_encrypted',
        'sender_name',
        'sender_email',
        'reply_to_email',
        'default_folder_id',
        'webhook_secret',
        'last_connection_test_at',
        'last_connection_test_status',
        'last_connection_test_message',
    ];

    protected static function booted(): void
    {
        static::creating(function (AudienceBrevoSetting $setting): void {
            if (! $setting->webhook_secret) {
                $setting->webhook_secret = Str::random(48);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'api_key_encrypted' => 'encrypted',
            'default_folder_id' => 'integer',
            'last_connection_test_at' => 'datetime',
        ];
    }

    public static function current(): self
    {
        return self::query()->firstOrCreate([], [
            'is_enabled' => false,
            'webhook_secret' => Str::random(48),
        ]);
    }

    public function hasApiKey(): bool
    {
        return filled($this->api_key_encrypted);
    }

    public function maskedApiKey(): string
    {
        return $this->hasApiKey() ? 'Clé API enregistrée' : 'Aucune clé API';
    }

    public function webhookUrl(): string
    {
        return url('/webhooks/brevo/audience/' . $this->webhook_secret);
    }
}
