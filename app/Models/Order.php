<?php

namespace App\Models;

use App\Models\Concerns\HasAdminRouteKey;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasAdminRouteKey;

    public const STATUSES = [
        'pendiente' => 'Pendiente',
        'pagado' => 'Pagado',
        'enviado' => 'Enviado',
    ];

    protected $fillable = [
        'customer_name',
        'customer_phone',
        'customer_address',
        'customer_city',
        'customer_document',
        'reservation_date',
        'reservation_time',
        'notes',
        'status',
        'total',
        'store_id',
        'admin_token',
    ];

    protected $casts = [
        'reservation_date' => 'date',
    ];

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }

    public static function statusOptions(): array
    {
        return self::STATUSES;
    }

    public function items()
    {
        return $this->hasMany(\App\Models\OrderItem::class);
    }

    public function store()
    {
        return $this->belongsTo(\App\Models\Store::class);
    }
}
