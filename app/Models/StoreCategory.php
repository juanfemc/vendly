<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreCategory extends Model
{
    protected $fillable = [
        'store_id',
        'name',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
