<?php

namespace App\Modules\Conversations\Models;

use App\Models\User;
use App\Modules\Contacts\Models\Contact;
use App\Modules\Conversations\Enums\ConversationChannel;
use App\Modules\Conversations\Enums\ConversationStatus;
use App\Modules\Conversations\Enums\ConversationUrgency;
use App\Modules\Inquiries\Models\Inquiry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    protected $fillable = [
        'public_reference',
        'session_token_hash',
        'contact_id',
        'assigned_user_id',
        'channel',
        'status',
        'locale',
        'summary',
        'topic',
        'urgency',
        'qualification',
        'entry_url',
        'ai_enabled',
        'last_message_at',
        'human_handover_at',
        'closed_at',
    ];

    protected $hidden = [
        'session_token_hash',
    ];

    protected function casts(): array
    {
        return [
            'channel' => ConversationChannel::class,
            'status' => ConversationStatus::class,
            'urgency' => ConversationUrgency::class,
            'qualification' => 'array',
            'ai_enabled' => 'boolean',
            'last_message_at' => 'datetime',
            'human_handover_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function inquiry(): HasOne
    {
        return $this->hasOne(Inquiry::class);
    }

    public function publicMessages(): HasMany
    {
        return $this->messages()->where('visibility', 'public');
    }

    public function belongsToAnonymousToken(string $token): bool
    {
        return hash_equals((string) $this->session_token_hash, hash('sha256', $token));
    }
}
