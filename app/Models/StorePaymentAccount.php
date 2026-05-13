<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StorePaymentAccount extends Model
{
    public const PROVIDER_MERCADOPAGO = 'mercadopago';

    public const STATUS_CONNECTED = 'connected';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_DISCONNECTED = 'disconnected';

    protected $fillable = [
        'store_id',
        'provider',
        'access_token',
        'refresh_token',
        'public_key',
        'provider_user_id',
        'expires_at',
        'connected_at',
        'disconnected_at',
        'status',
    ];

    protected $casts = [
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
        'public_key' => 'encrypted',
        'expires_at' => 'datetime',
        'connected_at' => 'datetime',
        'disconnected_at' => 'datetime',
    ];

    public function isConnected(): bool
    {
        return $this->status === self::STATUS_CONNECTED
            && (! $this->expires_at || $this->expires_at->isFuture());
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
