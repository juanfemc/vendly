<?php

namespace App\Models;

use App\Models\Concerns\HasAdminRouteKey;
use App\Support\BrandTheme;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class Store extends Model
{
    use HasAdminRouteKey;

    private static ?bool $supportsReservationScheduleColumns = null;
    private static ?bool $supportsCommercialNoticeColumns = null;
    private static ?bool $supportsSubdomainColumn = null;

    public const PRODUCT_SEARCH_THRESHOLD = 20;

    public const PLAN_BASIC = 'basic';
    public const PLAN_PRO = 'pro';
    public const PLAN_PREMIUM = 'premium';

    public const BASIC_PRODUCT_LIMIT = 20;
    public const PRO_PRODUCT_LIMIT = 100;

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
        'plan',
        'slug',
        'subdomain',
        'whatsapp',
        'location',
        'business_hours',
        'announcement_items',
        'free_shipping_minimum',
        'reservation_available_days',
        'reservation_time_start',
        'reservation_time_end',
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
        'announcement_items' => 'array',
        'free_shipping_minimum' => 'decimal:2',
        'reservation_available_days' => 'array',
        'responsive_product_columns' => 'integer',
        'show_hero_products_action' => 'boolean',
    ];

    public function isRestaurant(): bool
    {
        return $this->business_type === 'restaurant';
    }

    public static function planOptions(): array
    {
        return [
            self::PLAN_BASIC => 'Basico',
            self::PLAN_PRO => 'Pro',
            self::PLAN_PREMIUM => 'Premium',
        ];
    }

    public function planLabel(): string
    {
        return self::planOptions()[$this->plan ?? self::PLAN_PRO] ?? 'Pro';
    }

    public function isBasicPlan(): bool
    {
        return ($this->plan ?? self::PLAN_PRO) === self::PLAN_BASIC;
    }

    public function productLimit(): ?int
    {
        return match ($this->plan ?? self::PLAN_PRO) {
            self::PLAN_BASIC => self::BASIC_PRODUCT_LIMIT,
            self::PLAN_PRO => self::PRO_PRODUCT_LIMIT,
            default => null,
        };
    }

    public function canCreateMoreProducts(): bool
    {
        $limit = $this->productLimit();

        return $limit === null || $this->products()->count() < $limit;
    }

    public function allowsCategories(): bool
    {
        return ! $this->isBasicPlan();
    }

    public function allowsCommercialNotices(): bool
    {
        return ! $this->isBasicPlan();
    }

    public function allowsVisitStats(): bool
    {
        return ! $this->isBasicPlan();
    }

    public function allowsProductGallery(): bool
    {
        return ! $this->isBasicPlan();
    }

    public function allowsFullCustomization(): bool
    {
        return ! $this->isBasicPlan();
    }

    public function allowsSubdomain(): bool
    {
        return ! $this->isBasicPlan();
    }

    public static function reservedSubdomains(): array
    {
        return [
            'admin',
            'api',
            'app',
            'assets',
            'blog',
            'cdn',
            'mail',
            'panel',
            'soporte',
            'support',
            'www',
        ];
    }

    public static function normalizeSubdomain(?string $value): ?string
    {
        $value = strtolower(trim((string) $value));
        $value = preg_replace('/[^a-z0-9-]+/', '-', $value) ?? '';
        $value = trim(preg_replace('/-+/', '-', $value) ?? '', '-');

        return $value === '' ? null : $value;
    }

    public static function supportsSubdomainColumn(): bool
    {
        return self::$supportsSubdomainColumn ??= Schema::hasColumn('stores', 'subdomain');
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

    public static function reservationDayOptions(): array
    {
        return [
            'monday' => 'Lunes',
            'tuesday' => 'Martes',
            'wednesday' => 'Miercoles',
            'thursday' => 'Jueves',
            'friday' => 'Viernes',
            'saturday' => 'Sabado',
            'sunday' => 'Domingo',
        ];
    }

    public static function supportsReservationScheduleColumns(): bool
    {
        return self::$supportsReservationScheduleColumns ??= Schema::hasColumn('stores', 'reservation_available_days')
            && Schema::hasColumn('stores', 'reservation_time_start')
            && Schema::hasColumn('stores', 'reservation_time_end');
    }

    public static function supportsCommercialNoticeColumns(): bool
    {
        return self::$supportsCommercialNoticeColumns ??= Schema::hasColumn('stores', 'announcement_items')
            && Schema::hasColumn('stores', 'free_shipping_minimum');
    }

    public function announcementMessages(): array
    {
        if (! $this->allowsCommercialNotices()) {
            return [];
        }

        $messages = collect($this->announcement_items ?? [])
            ->pluck('text')
            ->map(fn ($text) => trim((string) $text))
            ->filter()
            ->take(5)
            ->values();

        if ($this->free_shipping_minimum !== null && (float) $this->free_shipping_minimum > 0) {
            $messages->prepend('Envio gratis desde $' . number_format((float) $this->free_shipping_minimum, 0, ',', '.'));
        }

        return $messages->unique()->values()->all();
    }

    public function reservationScheduleSummary(): ?string
    {
        if (! $this->isReservationStore() || ! self::supportsReservationScheduleColumns()) {
            return null;
        }

        $parts = [];
        $days = collect($this->reservation_available_days ?? [])
            ->map(fn (string $day) => self::reservationDayOptions()[$day] ?? null)
            ->filter()
            ->values();

        if ($days->isNotEmpty()) {
            $parts[] = 'Dias disponibles: ' . $days->implode(', ');
        }

        if ($this->reservation_time_start && $this->reservation_time_end) {
            $parts[] = 'Horario de reservas: ' . $this->reservation_time_start . ' - ' . $this->reservation_time_end;
        }

        return empty($parts) ? null : implode("\n", $parts);
    }

    public function allowsReservationDateTime(?string $date, ?string $time): bool
    {
        if (! $this->isReservationStore() || ! self::supportsReservationScheduleColumns()) {
            return true;
        }

        if ($date && ! empty($this->reservation_available_days)) {
            $day = strtolower(\Carbon\Carbon::parse($date)->englishDayOfWeek);

            if (! in_array($day, $this->reservation_available_days ?? [], true)) {
                return false;
            }
        }

        if ($time && $this->reservation_time_start && $this->reservation_time_end) {
            return $time >= $this->reservation_time_start && $time <= $this->reservation_time_end;
        }

        return true;
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

        $message = rawurlencode('Hola, quiero contactar a ' . $this->name);

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
        return $this->automaticTextColorFor($this->themeBackgroundColor());
    }

    public function themeFontFamily(): string
    {
        return self::FONT_FAMILIES[$this->font_family]['css'] ?? self::FONT_FAMILIES['system']['css'];
    }

    public function storefrontCssVariables(BrandTheme $brandTheme, int $responsiveProductColumns): string
    {
        $navBackground = BrandTheme::mixWithWhite($brandTheme->color, 0.1);

        return implode('; ', [
            '--brand-color: ' . $brandTheme->color,
            '--brand-contrast: ' . $brandTheme->contrast,
            '--store-nav-text: ' . BrandTheme::contrastFor($navBackground),
            '--responsive-product-columns: ' . $responsiveProductColumns,
            '--store-bg: ' . $this->themeBackgroundColor(),
            '--store-text: ' . $this->themeTextColor(),
            '--store-font: ' . $this->themeFontFamily(),
        ]) . ';';
    }

    private function themeColor(?string $color, string $fallback): string
    {
        return BrandTheme::normalizeColor($color, $fallback);
    }

    public static function automaticTextColorFor(?string $backgroundColor): string
    {
        return BrandTheme::contrastFor($backgroundColor, '#ffffff');
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
        if (! $this->allowsCategories() || ! $this->exists || $this->categories()->exists()) {
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
        if (! $this->allowsCategories()) {
            return [];
        }

        if (! $this->exists) {
            return $this->defaultProductCategoryOptions();
        }

        $this->ensureCategoryRecords();

        $savedCategories = $this->categories()
            ->orderedForDisplay()
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
