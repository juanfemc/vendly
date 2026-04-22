<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    public const BUSINESS_TYPES = [
        'store' => 'Tienda',
        'restaurant' => 'Restaurante',
        'technology' => 'Tecnologia',
        'supplements' => 'Suplementos',
    ];

    protected $fillable = [
        'user_id',
        'created_by_admin_id',
        'is_active',
        'views_count',
        'name',
        'business_type',
        'slug',
        'whatsapp',
        'shop_copy',
        'cover_image',
        'logo_image',
        'brand_color',
        'instagram_url',
        'facebook_url',
        'tiktok_url',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'views_count' => 'integer',
    ];

    public function isRestaurant(): bool
    {
        return $this->business_type === 'restaurant';
    }

    public function isTechnologyStore(): bool
    {
        return $this->business_type === 'technology';
    }

    public function isSupplementStore(): bool
    {
        return $this->business_type === 'supplements';
    }

    public function businessTypeLabel(): string
    {
        return self::BUSINESS_TYPES[$this->business_type] ?? 'Tienda';
    }

    public function isAvailable(): bool
    {
        return $this->is_active && (bool) $this->user?->isActive();
    }

    public function scopePubliclyAvailable($query)
    {
        $today = now()->toDateString();

        return $query
            ->where('is_active', true)
            ->whereHas('user', function ($query) use ($today) {
                $query
                    ->where('is_active', true)
                    ->where(function ($query) use ($today) {
                        $query
                            ->whereNull('active_starts_at')
                            ->orWhereDate('active_starts_at', '<=', $today);
                    })
                    ->where(function ($query) use ($today) {
                        $query
                            ->whereNull('active_ends_at')
                            ->orWhereDate('active_ends_at', '>=', $today);
                    });
            });
    }

    public static function businessTypeOptions(): array
    {
        return self::BUSINESS_TYPES;
    }

    public function defaultProductCategoryOptions(): array
    {
        if ($this->isRestaurant()) {
            return [
                'Entradas',
                'Platos fuertes',
                'Combos',
                'Bebidas',
                'Postres',
                'Desayunos',
            ];
        }

        if ($this->isTechnologyStore()) {
            return [
                'Tecnologia',
                'Audio',
                'Computo',
                'Gaming',
                'Accesorios',
                'Smart Home',
            ];
        }

        if ($this->isSupplementStore()) {
            return [
                'Proteina',
                'Pre entreno',
                'Vitaminas',
                'Bienestar',
                'Recuperacion',
                'Accesorios fitness',
            ];
        }

        return [
            'Camisetas',
            'Pantalones',
            'Vestidos',
            'Chaquetas',
            'Zapatos',
            'Accesorios',
            'Tecnologia',
            'Suplementos',
        ];
    }

    public function ensureCategoryRecords(): void
    {
        if (! $this->exists || $this->categories()->exists()) {
            return;
        }

        foreach ($this->defaultProductCategoryOptions() as $categoryName) {
            $this->categories()->create([
                'name' => $categoryName,
            ]);
        }
    }

    public function productCategoryOptions(): array
    {
        if (! $this->exists) {
            return $this->defaultProductCategoryOptions();
        }

        $this->ensureCategoryRecords();

        $savedCategories = $this->categories()
            ->orderBy('name')
            ->pluck('name');

        $productCategories = $this->products()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->pluck('category');

        return $savedCategories
            ->merge($productCategories)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function creatorAdmin()
    {
        return $this->belongsTo(User::class, 'created_by_admin_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function banners()
    {
        return $this->hasMany(StoreBanner::class);
    }

    public function categories()
    {
        return $this->hasMany(StoreCategory::class);
    }
}
