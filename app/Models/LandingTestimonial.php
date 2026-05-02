<?php

namespace App\Models;

use App\Models\Concerns\HasAdminRouteKey;
use Illuminate\Database\Eloquent\Model;

class LandingTestimonial extends Model
{
    use HasAdminRouteKey;

    protected $fillable = [
        'name',
        'role',
        'initials',
        'quote',
        'is_active',
        'sort_order',
        'admin_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
