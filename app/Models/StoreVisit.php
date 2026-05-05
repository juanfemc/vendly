<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreVisit extends Model
{
    protected $fillable = [
        'store_id',
        'ip_hash',
        'user_agent_hash',
        'visited_on',
        'visited_at',
    ];

    protected $casts = [
        'visited_on' => 'date',
        'visited_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
