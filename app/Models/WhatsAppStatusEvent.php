<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppStatusEvent extends Model
{
    protected $table = 'whatsapp_status_events';

    protected $fillable = [
        'provider_message_id',
        'status',
        'error',
        'occurred_at',
    ];

    protected $casts = [
        'error' => 'encrypted',
        'occurred_at' => 'datetime',
    ];
}
