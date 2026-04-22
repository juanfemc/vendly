<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'quantity',
        'price',
        'size',
        'color',
    ];

    public function displayName(): string
    {
        return $this->product_name ?: $this->product?->name ?: 'Producto eliminado';
    }

    public function product()
    {
        return $this->belongsTo(\App\Models\Product::class)->withDefault([
            'name' => 'Producto eliminado',
        ]);
    }
}
