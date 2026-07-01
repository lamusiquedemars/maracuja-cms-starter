<?php

namespace App\Modules\Audience\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SegmentMessage extends Model
{
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
        return $this->status === 'sent';
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

                if ($path && $mailMessage && method_exists($mailMessage, 'embed')) {
                    return '<img' . $matches[1] . 'src="' . e($mailMessage->embed($path)) . '"' . $matches[4] . '>';
                }

                $urlPath = parse_url($matches[3], PHP_URL_PATH);

                if ($path && is_string($urlPath)) {
                    return '<img' . $matches[1] . 'src="' . e(url($urlPath)) . '"' . $matches[4] . '>';
                }

                return $matches[0];
            },
            $body,
        ) ?? $body;
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
}
