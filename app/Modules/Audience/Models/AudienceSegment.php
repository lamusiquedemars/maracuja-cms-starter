<?php

namespace App\Modules\Audience\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AudienceSegment extends Model
{
    protected $fillable = [
        'name',
        'description',
        'brevo_list_id',
        'brevo_synced_at',
        'brevo_sync_status',
        'brevo_sync_error',
    ];

    protected function casts(): array
    {
        return [
            'brevo_list_id' => 'integer',
            'brevo_synced_at' => 'datetime',
        ];
    }

    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(AudienceContact::class, 'audience_contact_segment');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SegmentMessage::class);
    }
}
