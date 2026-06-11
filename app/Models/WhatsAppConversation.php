<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsAppConversation extends Model
{
    protected $table = 'whatsapp_conversations';

    protected $fillable = [
        'store_id',
        'user_id',
        'conversation_key',
        'contact_phone_hash',
        'contact_phone',
        'contact_name',
        'status',
        'unread_count',
        'last_customer_message_at',
        'last_message_at',
    ];

    protected $casts = [
        'contact_phone' => 'encrypted',
        'contact_name' => 'encrypted',
        'unread_count' => 'integer',
        'last_customer_message_at' => 'datetime',
        'last_message_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(WhatsAppChatMessage::class, 'conversation_id');
    }

    public function canSendFreeText(): bool
    {
        return $this->last_customer_message_at?->greaterThanOrEqualTo(now()->subDay()) ?? false;
    }
}
