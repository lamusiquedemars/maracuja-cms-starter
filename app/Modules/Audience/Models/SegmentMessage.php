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
    public const STATUS_QUEUED = 'queued';
    public const STATUS_SENDING = 'sending';
    public const STATUS_SENT = 'sent';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'audience_segment_id',
        'subject',
        'body',
        'status',
        'recipients_count',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
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

    /**
     * The campaign report distinguishes app/SMPP handoff from true delivery:
     * "sent" means the configured mail server accepted the message.
     */
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
            'accepted' => (int) ($counts[SegmentMessageDelivery::STATUS_SENT] ?? 0),
            'failed' => (int) ($counts[SegmentMessageDelivery::STATUS_FAILED] ?? 0),
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
}
