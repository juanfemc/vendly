<?php

namespace App\Models;

use App\Models\Concerns\HasAdminRouteKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasAdminRouteKey;

    protected $fillable = [
        'name',
        'slug',
        'category',
        'material',
        'price',
        'description',
        'features',
        'sizes',
        'colors',
        'image',
        'user_id',
        'store_id',
        'admin_token',
    ];

    protected $casts = [
        'sizes' => 'array',
        'colors' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (Product $product) {
            if (! $product->slug && $product->name && $product->store_id) {
                $product->slug = self::uniqueSlugFor((int) $product->store_id, $product->name, $product->id);
            }
        });
    }

    public static function uniqueSlugFor(int $storeId, string $name, ?int $ignoreProductId = null): string
    {
        $baseSlug = (Str::slug($name) ?: 'producto') . '-' . Str::lower(Str::random(6));
        $slug = $baseSlug;
        $counter = 2;

        while (
            self::where('store_id', $storeId)
                ->where('slug', $slug)
                ->when($ignoreProductId, fn ($query) => $query->whereKeyNot($ignoreProductId))
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    public function publicRouteKey(): string
    {
        if (! $this->slug && $this->name && $this->store_id) {
            $this->forceFill([
                'slug' => self::uniqueSlugFor((int) $this->store_id, $this->name, $this->id),
            ])->save();
        }

        return $this->slug;
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function hasSizes(): bool
    {
        return ! empty($this->sizes);
    }

    public function hasColors(): bool
    {
        return ! empty($this->colors);
    }

    public function hasVariants(): bool
    {
        return $this->hasSizes() || $this->hasColors();
    }
}
