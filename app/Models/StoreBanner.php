<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreBanner extends Model
{
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
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
