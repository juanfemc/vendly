<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiCreditTransaction extends Model
{
    public const TYPE_MONTHLY_GRANT = 'monthly_grant';
    public const TYPE_PACKAGE_PURCHASE = 'package_purchase';
    public const TYPE_USAGE = 'usage';
    public const TYPE_REFUND = 'refund';
    public const TYPE_ADJUSTMENT = 'adjustment';

    protected $fillable = [
        'store_id',
        'user_id',
        'ai_generation_id',
        'type',
        'amount',
        'reason',
        'period',
        'package_key',
        'price_cop',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'integer',
        'price_cop' => 'integer',
        'metadata' => 'array',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function generation(): BelongsTo
    {
        return $this->belongsTo(AiGeneration::class, 'ai_generation_id');
    }
}
