<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrialSignupClaim extends Model
{
    protected $fillable = [
        'phone_hash',
        'store_id',
        'source',
        'claimed_at',
    ];

    protected $casts = [
        'claimed_at' => 'datetime',
    ];
}
