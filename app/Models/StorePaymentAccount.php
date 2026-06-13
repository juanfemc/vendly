<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StorePaymentAccount extends Model
{
    public const PROVIDER_MERCADOPAGO = 'mercadopago';
    public const PROVIDER_WOMPI = 'wompi';

    public const STATUS_CONNECTED = 'connected';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_DISCONNECTED = 'disconnected';

    public const MODE_SANDBOX = 'sandbox';
    public const MODE_PRODUCTION = 'production';

    protected $fillable = [
        'store_id',
        'provider',
        'access_token',
        'refresh_token',
        'public_key',
        'private_key',
        'events_secret',
        'integrity_secret',
        'mode',
        'settings',
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
        'private_key' => 'encrypted',
        'events_secret' => 'encrypted',
        'integrity_secret' => 'encrypted',
        'settings' => 'array',
        'expires_at' => 'datetime',
        'connected_at' => 'datetime',
        'disconnected_at' => 'datetime',
    ];

    public function isConnected(): bool
    {
        return $this->status === self::STATUS_CONNECTED
            && (! $this->expires_at || $this->expires_at->isFuture());
    }

    public function isWompiReady(): bool
    {
        return $this->provider === self::PROVIDER_WOMPI
            && $this->isConnected()
            && filled($this->public_key)
            && filled($this->private_key)
            && filled($this->events_secret)
            && filled($this->integrity_secret);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
