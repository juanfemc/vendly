<?php

namespace App\Models;

use App\Models\Concerns\HasAdminRouteKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Order extends Model
{
    use HasAdminRouteKey;

    public const PAYMENT_METHOD_WHATSAPP = 'whatsapp';
    public const PAYMENT_METHOD_MERCADOPAGO = 'mercadopago';

    public const PAYMENT_STATUS_NOT_REQUIRED = 'not_required';
    public const PAYMENT_STATUS_PENDING = 'pending';
    public const PAYMENT_STATUS_APPROVED = 'approved';
    public const PAYMENT_STATUS_REJECTED = 'rejected';
    public const PAYMENT_STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        'pendiente' => 'Pendiente',
        'pagado' => 'Pagado',
        'enviado' => 'Enviado',
        'devuelto' => 'Devuelto',
    ];

    public const PAYMENT_METHOD_LABELS = [
        self::PAYMENT_METHOD_WHATSAPP => 'WhatsApp',
        self::PAYMENT_METHOD_MERCADOPAGO => 'Mercado Pago',
    ];

    public const PAYMENT_STATUS_LABELS = [
        self::PAYMENT_STATUS_NOT_REQUIRED => 'No requerido',
        self::PAYMENT_STATUS_PENDING => 'Pendiente',
        self::PAYMENT_STATUS_APPROVED => 'Aprobado',
        self::PAYMENT_STATUS_REJECTED => 'Rechazado',
        self::PAYMENT_STATUS_CANCELLED => 'Cancelado',
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
        'payment_method',
        'payment_provider',
        'payment_status',
        'payment_provider_reference',
        'payment_preference_id',
        'payment_id',
        'paid_at',
        'payment_expires_at',
        'total',
        'store_id',
        'admin_token',
    ];

    protected $casts = [
        'reservation_date' => 'date',
        'paid_at' => 'datetime',
        'payment_expires_at' => 'datetime',
    ];

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }

    public static function statusOptions(): array
    {
        return self::STATUSES;
    }

    public static function supportsPaymentExpirationColumn(): bool
    {
        return Schema::hasColumn('orders', 'payment_expires_at');
    }

    public function paymentMethodLabel(): string
    {
        return self::PAYMENT_METHOD_LABELS[$this->payment_method] ?? 'WhatsApp';
    }

    public function paymentStatusLabel(): string
    {
        return self::PAYMENT_STATUS_LABELS[$this->payment_status] ?? ucfirst((string) $this->payment_status);
    }

    public function paymentStatusBadgeClass(): string
    {
        return match ($this->payment_status) {
            self::PAYMENT_STATUS_APPROVED => 'resource-badge--success',
            self::PAYMENT_STATUS_REJECTED,
            self::PAYMENT_STATUS_CANCELLED => 'resource-badge--danger',
            self::PAYMENT_STATUS_PENDING => 'resource-badge--warning',
            default => '',
        };
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
