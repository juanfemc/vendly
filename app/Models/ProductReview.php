<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class ProductReview extends Model
{
    private static ?bool $supportsTable = null;

    protected $fillable = [
        'store_id',
        'product_id',
        'name',
        'rating',
        'comment',
        'is_approved',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_approved' => 'boolean',
    ];

    public static function supportsTable(): bool
    {
        return self::$supportsTable ??= Schema::hasTable('product_reviews');
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
