<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppMessage extends Model
{
    protected $table = 'whatsapp_messages';

    public const STATUS_QUEUED = 'queued';
    public const STATUS_RETRYING = 'retrying';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SENT = 'sent';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_READ = 'read';
    public const STATUS_FAILED = 'failed';
    public const STATUS_UNKNOWN = 'unknown';

    protected $fillable = [
        'store_id',
        'user_id',
        'audience',
        'template',
        'recipient_hash',
        'recipient',
        'parameters',
        'fingerprint',
        'status',
        'attempts',
        'last_attempt_at',
        'provider_message_id',
        'error',
        'sent_at',
        'delivered_at',
        'read_at',
        'failed_at',
    ];

    protected $casts = [
        'recipient' => 'encrypted',
        'parameters' => 'encrypted:array',
        'error' => 'encrypted',
        'attempts' => 'integer',
        'last_attempt_at' => 'datetime',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'failed_at' => 'datetime',
    ];
}
