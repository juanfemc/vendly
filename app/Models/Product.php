<?php

namespace App\Models;

use App\Models\Concerns\HasAdminRouteKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasAdminRouteKey;

    private static ?bool $supportsInventoryColumns = null;
    private static ?bool $supportsOfferColumn = null;
    private static ?bool $supportsOfferPricingColumn = null;

    protected $fillable = [
        'name',
        'slug',
        'category',
        'material',
        'price',
        'stock_quantity',
        'is_sold_out',
        'has_offer',
        'offer_original_price',
        'description',
        'features',
        'sizes',
        'colors',
        'image',
        'images',
        'user_id',
        'store_id',
        'admin_token',
    ];

    protected $casts = [
        'sizes' => 'array',
        'colors' => 'array',
        'images' => 'array',
        'stock_quantity' => 'integer',
        'is_sold_out' => 'boolean',
        'has_offer' => 'boolean',
        'offer_original_price' => 'decimal:2',
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

    public function usesInventory(): bool
    {
        return self::supportsInventoryColumns() && ! $this->store?->isReservationStore();
    }

    public static function supportsInventoryColumns(): bool
    {
        return self::$supportsInventoryColumns ??= Schema::hasColumn('products', 'stock_quantity')
            && Schema::hasColumn('products', 'is_sold_out');
    }

    public static function supportsOfferColumn(): bool
    {
        return self::$supportsOfferColumn ??= Schema::hasColumn('products', 'has_offer');
    }

    public static function supportsOfferPricingColumn(): bool
    {
        return self::$supportsOfferPricingColumn ??= Schema::hasColumn('products', 'offer_original_price');
    }

    public function hasOfferBadge(): bool
    {
        return self::supportsOfferColumn() && (bool) $this->has_offer;
    }

    public function hasOfferPricing(): bool
    {
        if (! $this->hasOfferBadge() || ! self::supportsOfferPricingColumn() || $this->offer_original_price === null) {
            return false;
        }

        return (float) $this->offer_original_price > (float) $this->price;
    }

    public function hasLimitedStock(): bool
    {
        return $this->usesInventory() && $this->stock_quantity !== null;
    }

    public function isSoldOut(): bool
    {
        if (! $this->usesInventory()) {
            return false;
        }

        return (bool) $this->is_sold_out || ($this->stock_quantity !== null && $this->stock_quantity <= 0);
    }

    public function hasEnoughStock(int $quantity): bool
    {
        if (! $this->usesInventory()) {
            return true;
        }

        if ($this->isSoldOut()) {
            return false;
        }

        return $this->stock_quantity === null || $quantity <= $this->stock_quantity;
    }

    public function stockLabel(): ?string
    {
        if (! $this->usesInventory()) {
            return null;
        }

        if ($this->isSoldOut()) {
            return 'Agotado';
        }

        if ($this->stock_quantity !== null) {
            return $this->stock_quantity . ' disponible' . ($this->stock_quantity === 1 ? '' : 's');
        }

        return null;
    }
}
