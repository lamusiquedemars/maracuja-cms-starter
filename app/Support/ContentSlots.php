<?php

namespace App\Support;

use Maracuja\SiteCore\Foundation\Models\ContentSlot;

class ContentSlots
{
    public static function value(string $key, ?string $fallback = null): ?string
    {
        if (! Modules::enabled('content_slots')) {
            return $fallback;
        }

        $value = ContentSlot::query()
            ->where('key', $key)
            ->value('value');

        return filled($value) ? $value : $fallback;
    }
}
