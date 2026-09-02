<?php

namespace App\Modules\Acquisition\Models;

use Illuminate\Database\Eloquent\Model;

class AcquisitionReportingSnapshot extends Model
{
    protected $fillable = ['site_reference', 'payload', 'fetched_at', 'last_error'];

    protected function casts(): array
    {
        return ['payload' => 'array', 'fetched_at' => 'datetime'];
    }

    public static function current(): ?self
    {
        return static::query()->where('site_reference', config('maracuja.acquisition.cremona.site_reference'))->first();
    }
}
