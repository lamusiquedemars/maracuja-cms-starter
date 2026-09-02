<?php

namespace App\Modules\Acquisition\Models;

use App\Modules\Inquiries\Models\Inquiry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcquisitionDelivery extends Model
{
    protected $fillable = ['inquiry_id', 'idempotency_key', 'payload', 'status', 'attempts', 'response_status', 'last_error', 'last_attempt_at', 'sent_at'];

    protected $hidden = ['payload'];

    protected function casts(): array
    {
        return ['payload' => 'encrypted:array', 'last_attempt_at' => 'datetime', 'sent_at' => 'datetime'];
    }

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class);
    }
}
