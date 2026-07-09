<?php

namespace App\Modules\Audience\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SegmentMessage extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_READY = 'ready';
    public const STATUS_QUEUED = 'queued';
    public const STATUS_SYNCING_TO_BREVO = 'syncing_to_brevo';
    public const STATUS_SYNC_FAILED = 'sync_failed';
    public const STATUS_CREATED_IN_BREVO = 'created_in_brevo';
    public const STATUS_SENDING = 'sending';
    public const STATUS_SENT_TO_PROVIDER = 'sent_to_provider';
    public const STATUS_SENT = 'sent';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_ARCHIVED = 'archived';

    public const PROVIDER_SMTP_LWS = 'smtp_lws';
    public const PROVIDER_BREVO = 'brevo';

    public const BREVO_STATUSES = [
        self::STATUS_SYNCING_TO_BREVO,
        self::STATUS_SYNC_FAILED,
        self::STATUS_CREATED_IN_BREVO,
        self::STATUS_SENT_TO_PROVIDER,
        self::STATUS_COMPLETED,
    ];

    protected $fillable = [
        'audience_segment_id',
        'subject',
        'body',
        'status',
        'provider',
        'recipients_count',
        'sent_at',
        'brevo_campaign_id',
        'brevo_status',
        'brevo_created_at',
        'brevo_sent_at',
        'brevo_last_sync_at',
        'brevo_error',
        'content_snapshot_html',
        'subject_snapshot',
        'sender_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'brevo_campaign_id' => 'integer',
            'brevo_created_at' => 'datetime',
            'brevo_sent_at' => 'datetime',
            'brevo_last_sync_at' => 'datetime',
            'sender_snapshot' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (SegmentMessage $message): void {
            if (! $message->isDirty('provider') || $message->provider !== self::PROVIDER_SMTP_LWS) {
                return;
            }

            if (! in_array($message->status, self::BREVO_STATUSES, true)) {
                return;
            }

            $message->status = self::STATUS_DRAFT;
            $message->brevo_status = null;
            $message->brevo_error = null;

            if (! $message->brevo_campaign_id) {
                $message->brevo_created_at = null;
                $message->brevo_sent_at = null;
                $message->brevo_last_sync_at = null;
                $message->content_snapshot_html = null;
                $message->subject_snapshot = null;
                $message->sender_snapshot = null;
            }
        });
    }

    public function segment(): BelongsTo
    {
        return $this->belongsTo(AudienceSegment::class, 'audience_segment_id');
    }

    public function isSent(): bool
    {
        return $this->status === self::STATUS_SENT;
    }

    public function isDraft(): bool
    {
        return $this->status === null || $this->status === self::STATUS_DRAFT;
    }

    public function usesBrevo(): bool
    {
        return $this->provider === self::PROVIDER_BREVO;
    }

    public function isQueuedOrSending(): bool
    {
        return in_array($this->status, [self::STATUS_QUEUED, self::STATUS_SENDING], true);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(SegmentMessageDelivery::class);
    }

    public function bodyForEmail(?object $mailMessage = null): string
    {
        $body = (string) $this->body;

        return preg_replace_callback(
            '/<img\b([^>]*?)\bsrc=(["\'])(.*?)\2([^>]*)>/i',
            function (array $matches) use ($mailMessage): string {
                $path = $this->localPublicImagePath($matches[3]);
                $publicUrl = $this->publicImageUrl($matches[3]);

                if ($path && $mailMessage && method_exists($mailMessage, 'embed')) {
                    return '<img' . $matches[1] . 'src="' . e($mailMessage->embed($path)) . '"' . $matches[4] . '>';
                }

                if ($publicUrl) {
                    return '<img' . $matches[1] . 'src="' . e($publicUrl) . '"' . $matches[4] . '>';
                }

                return $matches[0];
            },
            $body,
        ) ?? $body;
    }

    public function deliveryReport(): array
    {
        $counts = $this->deliveries()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $targeted = $this->targetedRecipientsCount();
        $queued = (int) $this->deliveries()->count();
        $skipped = (int) ($counts[SegmentMessageDelivery::STATUS_SKIPPED] ?? 0);
        $excluded = max(0, $targeted - $queued) + $skipped;

        return [
            'targeted' => $targeted,
            'pending' => (int) ($counts[SegmentMessageDelivery::STATUS_PENDING] ?? 0),
            'sent_to_provider' => (int) ($counts[SegmentMessageDelivery::STATUS_SENT_TO_PROVIDER] ?? 0),
            'accepted' => (int) ($counts[SegmentMessageDelivery::STATUS_SENT] ?? 0),
            'delivered' => (int) ($counts[SegmentMessageDelivery::STATUS_DELIVERED] ?? 0),
            'opened' => (int) ($counts[SegmentMessageDelivery::STATUS_OPENED] ?? 0),
            'clicked' => (int) ($counts[SegmentMessageDelivery::STATUS_CLICKED] ?? 0),
            'soft_bounced' => (int) ($counts[SegmentMessageDelivery::STATUS_SOFT_BOUNCED] ?? 0),
            'hard_bounced' => (int) ($counts[SegmentMessageDelivery::STATUS_HARD_BOUNCED] ?? 0),
            'unsubscribed' => (int) ($counts[SegmentMessageDelivery::STATUS_UNSUBSCRIBED] ?? 0),
            'complained' => (int) ($counts[SegmentMessageDelivery::STATUS_COMPLAINED] ?? 0),
            'blocked' => (int) ($counts[SegmentMessageDelivery::STATUS_BLOCKED] ?? 0),
            'failed' => (int) ($counts[SegmentMessageDelivery::STATUS_FAILED] ?? 0),
            'error' => (int) ($counts[SegmentMessageDelivery::STATUS_ERROR] ?? 0),
            'sending' => (int) ($counts[SegmentMessageDelivery::STATUS_SENDING] ?? 0),
            'excluded' => $excluded,
        ];
    }

    public function targetedRecipientsCount(): int
    {
        return $this->segment?->contacts()->count() ?? 0;
    }

    public function hasProcessableDeliveries(): bool
    {
        return $this->deliveries()
            ->whereIn('status', [
                SegmentMessageDelivery::STATUS_PENDING,
                SegmentMessageDelivery::STATUS_FAILED,
            ])
            ->exists();
    }

    public function publicImageWarnings(): array
    {
        preg_match_all('/<img\b[^>]*?\bsrc=(["\'])(.*?)\1/i', (string) $this->body, $matches);

        return collect($matches[2] ?? [])
            ->filter(fn (string $src): bool => $this->imageNeedsPublicWarning($src))
            ->map(fn (string $src): string => "Image non publique: {$src}")
            ->values()
            ->all();
    }

    public function hasPublicImageWarnings(): bool
    {
        return $this->publicImageWarnings() !== [];
    }

    private function localPublicImagePath(string $src): ?string
    {
        $path = parse_url($src, PHP_URL_PATH);

        if (! is_string($path) || ! Str::startsWith($path, '/storage/')) {
            return null;
        }

        $relativePath = Str::after($path, '/storage/');

        if (! Storage::disk('public')->exists($relativePath)) {
            return null;
        }

        return Storage::disk('public')->path($relativePath);
    }

    private function publicImageUrl(string $src): ?string
    {
        $path = parse_url($src, PHP_URL_PATH);

        if (! is_string($path) || ! Str::startsWith($path, '/storage/')) {
            return null;
        }

        return url($path);
    }

    private function imageNeedsPublicWarning(string $src): bool
    {
        $host = parse_url($src, PHP_URL_HOST);

        if (is_string($host)) {
            return in_array($host, ['localhost', '127.0.0.1', '::1'], true)
                || Str::endsWith($host, ['.local', '.test']);
        }

        $path = parse_url($src, PHP_URL_PATH);

        if (! is_string($path) || ! Str::startsWith($path, '/storage/')) {
            return false;
        }

        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        return ! is_string($appHost)
            || in_array($appHost, ['localhost', '127.0.0.1', '::1'], true)
            || Str::endsWith($appHost, ['.local', '.test']);
    }
}
