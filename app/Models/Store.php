<?php

namespace App\Models;

use App\Models\Concerns\HasAdminRouteKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    use HasAdminRouteKey;

    public const PRODUCT_SEARCH_THRESHOLD = 20;

    public const FONT_FAMILIES = [
        'system' => [
            'label' => 'Sistema moderna',
            'css' => 'Arial, sans-serif',
        ],
        'serif' => [
            'label' => 'Editorial serif',
            'css' => 'Georgia, "Times New Roman", serif',
        ],
        'rounded' => [
            'label' => 'Redondeada',
            'css' => '"Trebuchet MS", Arial, sans-serif',
        ],
        'mono' => [
            'label' => 'Monoespaciada',
            'css' => '"Courier New", monospace',
        ],
    ];

    public const BUSINESS_TYPES = [
        'store' => 'Tienda',
        'restaurant' => 'Restaurante',
        'technology' => 'Tecnologia',
        'supplements' => 'Suplementos',
        'reservations' => 'Reservas',
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
        'location',
        'business_hours',
        'shop_copy',
        'mission',
        'vision',
        'cover_image',
        'logo_image',
        'brand_color',
        'background_color',
        'text_color',
        'font_family',
        'responsive_product_columns',
        'show_hero_products_action',
        'instagram_url',
        'facebook_url',
        'tiktok_url',
        'admin_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'views_count' => 'integer',
        'responsive_product_columns' => 'integer',
        'show_hero_products_action' => 'boolean',
    ];

    public function isRestaurant(): bool
    {
        return $this->business_type === 'restaurant';
    }

    public function visits(): HasMany
    {
        return $this->hasMany(StoreVisit::class);
    }

    public function isTechnologyStore(): bool
    {
        return $this->business_type === 'technology';
    }

    public function isSupplementStore(): bool
    {
        return $this->business_type === 'supplements';
    }

    public function isReservationStore(): bool
    {
        return $this->business_type === 'reservations';
    }

    public function businessTypeLabel(): string
    {
        return self::BUSINESS_TYPES[$this->business_type] ?? 'Tienda';
    }

    public function hasAboutContent(): bool
    {
        return trim((string) $this->mission) !== '' && trim((string) $this->vision) !== '';
    }

    public function hasProductSearch(): bool
    {
        if (! $this->exists) {
            return false;
        }

        return $this->products()->count() > self::PRODUCT_SEARCH_THRESHOLD;
    }

    public function whatsappNumber(): string
    {
        return preg_replace('/\D+/', '', (string) $this->whatsapp);
    }

    public function whatsappInfoUrl(): ?string
    {
        $number = $this->whatsappNumber();

        if ($number === '') {
            return null;
        }

        $message = rawurlencode('Hola, quiero mas informacion sobre ' . $this->name);

        return "https://wa.me/{$number}?text={$message}";
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

    public static function fontFamilyOptions(): array
    {
        return collect(self::FONT_FAMILIES)
            ->mapWithKeys(fn (array $font, string $value) => [$value => $font['label']])
            ->all();
    }

    public function themeBackgroundColor(): string
    {
        return $this->themeColor($this->background_color, '#ffffff');
    }

    public function themeTextColor(): string
    {
        return $this->themeColor($this->text_color, '#171717');
    }

    public function themeFontFamily(): string
    {
        return self::FONT_FAMILIES[$this->font_family]['css'] ?? self::FONT_FAMILIES['system']['css'];
    }

    public function storefrontCssVariables($brandTheme, int $responsiveProductColumns): string
    {
        return implode('; ', [
            '--brand-color: ' . $brandTheme->color,
            '--brand-contrast: ' . $brandTheme->contrast,
            '--responsive-product-columns: ' . $responsiveProductColumns,
            '--store-bg: ' . $this->themeBackgroundColor(),
            '--store-text: ' . $this->themeTextColor(),
            '--store-font: ' . $this->themeFontFamily(),
        ]) . ';';
    }

    private function themeColor(?string $color, string $fallback): string
    {
        $color = trim((string) $color);

        return preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $color) ? $color : $fallback;
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

        if ($this->isReservationStore()) {
            return [
                'Consultas',
                'Citas',
                'Servicios',
                'Experiencias',
                'Paquetes',
                'Eventos',
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
                'slug' => StoreCategory::uniqueSlugFor((int) $this->id, $categoryName),
                'is_active' => true,
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
            ->orderBy('sort_order')
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
