<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppChatMessage extends Model
{
    protected $table = 'whatsapp_chat_messages';

    public const DIRECTION_INCOMING = 'incoming';
    public const DIRECTION_OUTGOING = 'outgoing';

    public const STATUS_RECEIVED = 'received';
    public const STATUS_SENT = 'sent';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_READ = 'read';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'conversation_id',
        'store_id',
        'sent_by_user_id',
        'direction',
        'message_type',
        'body',
        'media_id',
        'provider_message_id',
        'status',
        'error',
        'payload',
        'sent_at',
        'delivered_at',
        'read_at',
        'failed_at',
    ];

    protected $casts = [
        'body' => 'encrypted',
        'media_id' => 'encrypted',
        'error' => 'encrypted',
        'payload' => 'encrypted:array',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(WhatsAppConversation::class, 'conversation_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by_user_id');
    }
}
