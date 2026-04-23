<?php

namespace App\Models;

use App\Models\Concerns\HasAdminRouteKey;
use Illuminate\Database\Eloquent\Model;

class StoreBanner extends Model
{
    use HasAdminRouteKey;

    protected $fillable = [
        'store_id',
        'title',
        'subtitle',
        'image',
        'link',
        'is_active',
        'sort_order',
        'group_token',
        'applies_to_all',
        'admin_token',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
