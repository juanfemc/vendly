<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerFollowup extends Model
{
    public const TYPE_DAY_1_SETUP = 'day_1_setup';
    public const TYPE_DAY_3_PRODUCTS_OR_SHARE = 'day_3_products_or_share';
    public const TYPE_DAY_6_TRIAL_ENDING = 'day_6_trial_ending';
    public const TYPE_SUBSCRIPTION_3_DAYS_BEFORE = 'subscription_3_days_before';
    public const TYPE_SUBSCRIPTION_1_DAY_BEFORE = 'subscription_1_day_before';
    public const TYPE_SUBSCRIPTION_EXPIRED = 'subscription_expired';

    public const STATUS_PENDING = 'pending';
    public const STATUS_QUEUED = 'queued';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SKIPPED = 'skipped';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'store_id',
        'user_id',
        'whatsapp_message_id',
        'type',
        'context_key',
        'template',
        'parameters',
        'status',
        'scheduled_for',
        'sent_at',
        'failed_at',
        'skipped_at',
        'cancelled_at',
        'error',
    ];

    protected $casts = [
        'parameters' => 'encrypted:array',
        'error' => 'encrypted',
        'scheduled_for' => 'datetime',
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
        'skipped_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function whatsappMessage(): BelongsTo
    {
        return $this->belongsTo(WhatsAppMessage::class);
    }
}
