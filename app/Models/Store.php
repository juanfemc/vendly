<?php

namespace App\Models;

use App\Models\Concerns\HasAdminRouteKey;
use App\Support\BrandTheme;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class Store extends Model
{
    use HasAdminRouteKey;

    private static ?bool $supportsReservationScheduleColumns = null;
    private static ?bool $supportsCommercialNoticeColumns = null;
    private static ?bool $supportsSubdomainColumn = null;
    private static ?bool $supportsCustomDomainColumns = null;
    private static ?bool $supportsShippingMethodsColumn = null;
    private static ?bool $supportsLocalDeliveryColumns = null;
    private static ?bool $supportsLocalDeliveryCityCodeColumn = null;
    private static ?bool $supportsMetaPixelColumn = null;
    private static ?bool $supportsSubscriptionColumns = null;
    private static ?bool $supportsAiTables = null;

    public const PRODUCT_SEARCH_THRESHOLD = 20;
    public const TRIAL_DAYS = 7;

    public const PLAN_BASIC = 'basic';
    public const PLAN_PRO = 'pro';
    public const PLAN_PREMIUM = 'premium';

    public const SUBSCRIPTION_TRIALING = 'trialing';
    public const SUBSCRIPTION_ACTIVE = 'active';
    public const SUBSCRIPTION_EXPIRED = 'expired';
    public const SUBSCRIPTION_PAUSED = 'paused';

    public const CUSTOM_DOMAIN_PENDING = 'pending';
    public const CUSTOM_DOMAIN_VERIFIED = 'verified';
    public const CUSTOM_DOMAIN_FAILED = 'failed';

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
        'fashion' => 'Ropa',
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
        'subscription_status',
        'trial_starts_at',
        'trial_ends_at',
        'subscription_ends_at',
        'slug',
        'subdomain',
        'custom_domain',
        'custom_domain_status',
        'custom_domain_verified_at',
        'whatsapp',
        'whatsapp_consent_at',
        'whatsapp_consent_version',
        'whatsapp_consent_text',
        'whatsapp_consent_source',
        'whatsapp_consent_ip_hash',
        'location',
        'business_hours',
        'announcement_items',
        'free_shipping_minimum',
        'shipping_methods',
        'local_delivery_area',
        'local_delivery_city_code',
        'local_delivery_cost',
        'outside_delivery_cost',
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
        'meta_pixel_id',
        'admin_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'views_count' => 'integer',
        'announcement_items' => 'array',
        'free_shipping_minimum' => 'decimal:2',
        'shipping_methods' => 'array',
        'local_delivery_cost' => 'decimal:2',
        'outside_delivery_cost' => 'decimal:2',
        'reservation_available_days' => 'array',
        'responsive_product_columns' => 'integer',
        'show_hero_products_action' => 'boolean',
        'custom_domain_verified_at' => 'datetime',
        'trial_starts_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
        'whatsapp_consent_at' => 'datetime',
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

    public static function subscriptionStatusOptions(): array
    {
        return [
            self::SUBSCRIPTION_TRIALING => 'Prueba gratis',
            self::SUBSCRIPTION_ACTIVE => 'Activa',
            self::SUBSCRIPTION_EXPIRED => 'Vencida',
            self::SUBSCRIPTION_PAUSED => 'Pausada',
        ];
    }

    public function planLabel(): string
    {
        return self::planOptions()[$this->plan ?? self::PLAN_PRO] ?? 'Pro';
    }

    public function subscriptionStatusLabel(): string
    {
        return self::subscriptionStatusOptions()[$this->subscriptionStatus()] ?? 'Activa';
    }

    public function subscriptionStatus(): string
    {
        if (! self::supportsSubscriptionColumns()) {
            return self::SUBSCRIPTION_ACTIVE;
        }

        $status = trim((string) ($this->subscription_status ?: self::SUBSCRIPTION_ACTIVE));

        return array_key_exists($status, self::subscriptionStatusOptions())
            ? $status
            : self::SUBSCRIPTION_ACTIVE;
    }

    public function startTrial(int $days = self::TRIAL_DAYS, string $plan = self::PLAN_PREMIUM): void
    {
        $startsAt = now();
        $trialPlan = array_key_exists($plan, self::planOptions()) ? $plan : self::PLAN_PREMIUM;

        if (! self::supportsSubscriptionColumns()) {
            $this->forceFill(['plan' => $trialPlan])->save();

            return;
        }

        $this->forceFill([
            'plan' => $trialPlan,
            'subscription_status' => self::SUBSCRIPTION_TRIALING,
            'trial_starts_at' => $startsAt,
            'trial_ends_at' => $startsAt->copy()->addDays(max(1, $days)),
            'subscription_ends_at' => null,
        ])->save();
    }

    public function activateSubscription(int $days = 30, ?string $plan = null): void
    {
        $subscriptionPlan = array_key_exists((string) $plan, self::planOptions())
            ? (string) $plan
            : ($this->plan ?? self::PLAN_PRO);

        if (! self::supportsSubscriptionColumns()) {
            $this->forceFill([
                'is_active' => true,
                'plan' => $subscriptionPlan,
            ])->save();

            return;
        }

        $startsFrom = $this->subscriptionStatus() === self::SUBSCRIPTION_ACTIVE
            && $this->subscription_ends_at
            && $this->subscription_ends_at->copy()->endOfDay()->isFuture()
                ? $this->subscription_ends_at->copy()
                : now();

        $this->forceFill([
            'is_active' => true,
            'plan' => $subscriptionPlan,
            'subscription_status' => self::SUBSCRIPTION_ACTIVE,
            'trial_starts_at' => null,
            'trial_ends_at' => null,
            'subscription_ends_at' => $startsFrom->endOfDay()->addDays(max(1, $days)),
        ])->save();
    }

    public function isTrialing(): bool
    {
        return $this->subscriptionStatus() === self::SUBSCRIPTION_TRIALING
            && $this->trial_ends_at
            && $this->trial_ends_at->copy()->endOfDay()->isFuture();
    }

    public function trialExpired(): bool
    {
        return $this->subscriptionStatus() === self::SUBSCRIPTION_TRIALING
            && $this->trial_ends_at
            && $this->trial_ends_at->copy()->endOfDay()->isPast();
    }

    public function trialDaysRemaining(): int
    {
        if (! $this->isTrialing()) {
            return 0;
        }

        return (int) max(0, now()->startOfDay()->diffInDays($this->trial_ends_at->copy()->startOfDay(), false));
    }

    public function hasActiveSubscription(): bool
    {
        if (! self::supportsSubscriptionColumns()) {
            return true;
        }

        if ($this->isTrialing()) {
            return true;
        }

        if ($this->subscriptionStatus() !== self::SUBSCRIPTION_ACTIVE) {
            return false;
        }

        return ! $this->subscription_ends_at || $this->subscription_ends_at->copy()->endOfDay()->isFuture();
    }

    public function subscriptionEndsSoon(int $days = 3): bool
    {
        if (! self::supportsSubscriptionColumns()) {
            return false;
        }

        $endsAt = $this->subscriptionStatus() === self::SUBSCRIPTION_TRIALING
            ? $this->trial_ends_at
            : $this->subscription_ends_at;

        return $endsAt
            && $endsAt->copy()->endOfDay()->isFuture()
            && $endsAt->toDateString() <= now()->addDays($days)->toDateString();
    }

    public function subscriptionRemainingLabel(): string
    {
        if (! self::supportsSubscriptionColumns()) {
            return 'Activa';
        }

        if ($this->subscriptionExpired()) {
            return 'Vencida';
        }

        $endsAt = $this->subscriptionStatus() === self::SUBSCRIPTION_TRIALING
            ? $this->trial_ends_at
            : $this->subscription_ends_at;

        if (! $endsAt) {
            return $this->subscriptionStatusLabel();
        }

        if ($endsAt->isToday()) {
            return 'Vence hoy';
        }

        $days = (int) max(0, now()->startOfDay()->diffInDays($endsAt->copy()->startOfDay(), false));

        return $days === 1 ? '1 dia restante' : $days . ' dias restantes';
    }

    public function subscriptionExpired(): bool
    {
        if ($this->trialExpired()) {
            return true;
        }

        return $this->subscriptionStatus() === self::SUBSCRIPTION_EXPIRED
            || ($this->subscriptionStatus() === self::SUBSCRIPTION_ACTIVE
                && $this->subscription_ends_at
                && $this->subscription_ends_at->copy()->endOfDay()->isPast());
    }

    public static function expirePastSubscriptions(): int
    {
        if (! self::supportsSubscriptionColumns()) {
            return 0;
        }

        $today = now()->toDateString();

        return self::query()
            ->whereIn('subscription_status', [self::SUBSCRIPTION_TRIALING, self::SUBSCRIPTION_ACTIVE])
            ->where(function ($query) use ($today) {
                $query
                    ->where(function ($query) use ($today) {
                        $query
                            ->where('subscription_status', self::SUBSCRIPTION_TRIALING)
                            ->whereNotNull('trial_ends_at')
                            ->whereDate('trial_ends_at', '<', $today);
                    })
                    ->orWhere(function ($query) use ($today) {
                        $query
                            ->where('subscription_status', self::SUBSCRIPTION_ACTIVE)
                            ->whereNotNull('subscription_ends_at')
                            ->whereDate('subscription_ends_at', '<', $today);
                    });
            })
            ->update(['subscription_status' => self::SUBSCRIPTION_EXPIRED]);
    }

    public function scopeExpiredSubscriptions($query)
    {
        if (! self::supportsSubscriptionColumns()) {
            return $query->whereRaw('1 = 0');
        }

        $today = now()->toDateString();

        return $query->where(function ($query) use ($today) {
            $query
                ->where('subscription_status', self::SUBSCRIPTION_EXPIRED)
                ->orWhere(function ($query) use ($today) {
                    $query
                        ->where('subscription_status', self::SUBSCRIPTION_TRIALING)
                        ->whereNotNull('trial_ends_at')
                        ->whereDate('trial_ends_at', '<', $today);
                })
                ->orWhere(function ($query) use ($today) {
                    $query
                        ->where('subscription_status', self::SUBSCRIPTION_ACTIVE)
                        ->whereNotNull('subscription_ends_at')
                        ->whereDate('subscription_ends_at', '<', $today);
                });
        });
    }

    public function scopeSubscriptionsEndingWithin($query, int $days = 3)
    {
        if (! self::supportsSubscriptionColumns()) {
            return $query->whereRaw('1 = 0');
        }

        $today = now()->toDateString();
        $limit = now()->addDays(max(0, $days))->toDateString();

        return $query->where(function ($query) use ($today, $limit) {
            $query
                ->where(function ($query) use ($today, $limit) {
                    $query
                        ->where('subscription_status', self::SUBSCRIPTION_TRIALING)
                        ->whereNotNull('trial_ends_at')
                        ->whereDate('trial_ends_at', '>=', $today)
                        ->whereDate('trial_ends_at', '<=', $limit);
                })
                ->orWhere(function ($query) use ($today, $limit) {
                    $query
                        ->where('subscription_status', self::SUBSCRIPTION_ACTIVE)
                        ->whereNotNull('subscription_ends_at')
                        ->whereDate('subscription_ends_at', '>=', $today)
                        ->whereDate('subscription_ends_at', '<=', $limit);
                });
        });
    }

    public function onboardingChecklist(): array
    {
        return [
            'profile' => [
                'label' => 'Datos principales',
                'description' => 'Nombre, tipo de negocio y WhatsApp de pedidos.',
                'complete' => trim((string) $this->name) !== ''
                    && trim((string) $this->business_type) !== ''
                    && trim((string) $this->whatsapp) !== '',
            ],
            'location' => [
                'label' => 'Ubicacion',
                'description' => 'Ciudad o direccion visible para tus clientes.',
                'complete' => trim((string) $this->location) !== '',
            ],
            'identity' => [
                'label' => 'Identidad visual',
                'description' => 'Logo y color principal de la tienda.',
                'complete' => trim((string) $this->logo_image) !== ''
                    && trim((string) $this->brand_color) !== '',
            ],
            'catalog' => [
                'label' => 'Primer producto',
                'description' => 'Publica al menos un producto para abrir ventas.',
                'complete' => $this->exists && $this->products()->exists(),
            ],
        ];
    }

    public function onboardingProgress(): int
    {
        $checklist = collect($this->onboardingChecklist());

        if ($checklist->isEmpty()) {
            return 100;
        }

        $completed = $checklist->filter(fn (array $item) => (bool) ($item['complete'] ?? false))->count();

        return (int) round(($completed / $checklist->count()) * 100);
    }

    public function needsOnboarding(): bool
    {
        return $this->onboardingProgress() < 100;
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

    public function allowsShippingMethods(): bool
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

    public function allowsTemplates(): bool
    {
        return ! $this->isBasicPlan();
    }

    public function allowsCustomDomain(): bool
    {
        return ($this->plan ?? self::PLAN_PRO) === self::PLAN_PREMIUM;
    }

    public function allowsOnlinePayments(): bool
    {
        return ($this->plan ?? self::PLAN_PRO) === self::PLAN_PREMIUM;
    }

    public function allowsOfferBadges(): bool
    {
        return ($this->plan ?? self::PLAN_PRO) === self::PLAN_PREMIUM;
    }

    public function allowsCustomProductBadges(): bool
    {
        return ($this->plan ?? self::PLAN_PRO) === self::PLAN_PREMIUM;
    }

    public function allowsMetaPixel(): bool
    {
        return ($this->plan ?? self::PLAN_PRO) === self::PLAN_PREMIUM
            && self::supportsMetaPixelColumn();
    }

    public function allowsAiContent(): bool
    {
        return ($this->plan ?? self::PLAN_PRO) === self::PLAN_PREMIUM
            && filled(config('services.openai.key'))
            && self::supportsAiTables();
    }

    public function allowsProductReviews(): bool
    {
        return ! $this->isBasicPlan() && ProductReview::supportsTable();
    }

    public function hasOfferProducts(): bool
    {
        return $this->allowsOfferBadges()
            && Product::supportsOfferColumn()
            && $this->products()->where('has_offer', true)->exists();
    }

    public static function supportsShippingMethodsColumn(): bool
    {
        return self::$supportsShippingMethodsColumn ??= Schema::hasColumn('stores', 'shipping_methods');
    }

    public static function supportsAiTables(): bool
    {
        return self::$supportsAiTables ??= Schema::hasTable('ai_generations')
            && Schema::hasTable('ai_credit_transactions');
    }

    public static function supportsLocalDeliveryColumns(): bool
    {
        return self::$supportsLocalDeliveryColumns ??= Schema::hasColumn('stores', 'local_delivery_area')
            && Schema::hasColumn('stores', 'local_delivery_cost')
            && Schema::hasColumn('stores', 'outside_delivery_cost');
    }

    public static function supportsLocalDeliveryCityCodeColumn(): bool
    {
        return self::$supportsLocalDeliveryCityCodeColumn ??= Schema::hasColumn('stores', 'local_delivery_city_code');
    }

    public static function normalizeDeliveryCity(?string $value): string
    {
        $value = Str::ascii(Str::lower(trim((string) $value)));
        $value = preg_replace('/[^a-z0-9\s-]+/', ' ', $value) ?? '';

        return trim(preg_replace('/\s+/', ' ', $value) ?? '');
    }

    public function localDeliveryEnabled(): bool
    {
        return $this->allowsShippingMethods()
            && self::supportsLocalDeliveryColumns()
            && self::normalizeDeliveryCity($this->local_delivery_area) !== ''
            && $this->local_delivery_cost !== null
            && $this->outside_delivery_cost !== null;
    }

    public function deliveryByCity(?string $city, float $subtotal, ?string $cityCode = null): ?array
    {
        if (! $this->localDeliveryEnabled()) {
            return null;
        }

        $localCity = trim((string) $this->local_delivery_area);
        $isLocal = $this->isLocalDeliveryCity($city, $cityCode);
        $baseCost = max(0, (float) ($isLocal ? $this->local_delivery_cost : $this->outside_delivery_cost));

        return [
            'name' => $isLocal ? 'Envio local: ' . $localCity : 'Envio fuera de ' . $localCity,
            'cost' => $this->shippingCostForAmount($baseCost, $subtotal),
            'base_cost' => $baseCost,
            'is_local' => $isLocal,
        ];
    }

    public function shippingMethods(): array
    {
        if (! $this->allowsShippingMethods() || ! self::supportsShippingMethodsColumn()) {
            return [];
        }

        return collect($this->shipping_methods ?? [])
            ->map(function ($method, int $index) {
                $name = trim((string) ($method['name'] ?? ''));
                $cost = max(0, (float) ($method['cost'] ?? 0));

                if ($name === '') {
                    return null;
                }

                return [
                    'key' => (string) $index,
                    'name' => $name,
                    'cost' => $cost,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    public function shippingMethodByKey(?string $key): ?array
    {
        return collect($this->shippingMethods())->firstWhere('key', (string) $key);
    }

    public function shippingCostForSubtotal(array $method, float $subtotal): float
    {
        $cost = max(0, (float) ($method['cost'] ?? 0));

        return $this->shippingCostForAmount($cost, $subtotal);
    }

    public function shippingCostForAmount(float $cost, float $subtotal): float
    {
        $cost = max(0, $cost);

        if ($this->free_shipping_minimum !== null
            && $this->free_shipping_minimum > 0
            && $subtotal >= (float) $this->free_shipping_minimum
        ) {
            return 0;
        }

        return $cost;
    }

    private function isLocalDeliveryCity(?string $city, ?string $cityCode = null): bool
    {
        $localCityCode = self::supportsLocalDeliveryCityCodeColumn()
            ? trim((string) $this->local_delivery_city_code)
            : '';
        $customerCityCode = trim((string) $cityCode);

        if ($localCityCode !== '' && $customerCityCode !== '') {
            return $localCityCode === $customerCityCode;
        }

        return self::normalizeDeliveryCity($city) === self::normalizeDeliveryCity($this->local_delivery_area);
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

    public static function normalizeCustomDomain(?string $value): ?string
    {
        $value = strtolower(trim((string) $value));
        $value = preg_replace('#^https?://#', '', $value) ?? '';
        $value = preg_replace('#/.*$#', '', $value) ?? '';
        $value = preg_replace('/:\d+$/', '', $value) ?? '';
        $value = trim($value, ". \t\n\r\0\x0B");

        return $value === '' ? null : $value;
    }

    public static function supportsSubdomainColumn(): bool
    {
        return self::$supportsSubdomainColumn ??= Schema::hasColumn('stores', 'subdomain');
    }

    public static function supportsCustomDomainColumns(): bool
    {
        return self::$supportsCustomDomainColumns ??= Schema::hasColumn('stores', 'custom_domain')
            && Schema::hasColumn('stores', 'custom_domain_status')
            && Schema::hasColumn('stores', 'custom_domain_verified_at');
    }

    public static function supportsMetaPixelColumn(): bool
    {
        return self::$supportsMetaPixelColumn ??= Schema::hasColumn('stores', 'meta_pixel_id');
    }

    public static function supportsSubscriptionColumns(): bool
    {
        return self::$supportsSubscriptionColumns ??= Schema::hasColumn('stores', 'subscription_status')
            && Schema::hasColumn('stores', 'trial_starts_at')
            && Schema::hasColumn('stores', 'trial_ends_at')
            && Schema::hasColumn('stores', 'subscription_ends_at');
    }

    public function visits(): HasMany
    {
        return $this->hasMany(StoreVisit::class);
    }

    public function isTechnologyStore(): bool
    {
        return $this->business_type === 'technology';
    }

    public function isFashionStore(): bool
    {
        return $this->business_type === 'fashion';
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
        return $this->is_active
            && $this->hasActiveSubscription()
            && (bool) $this->user?->isActive();
    }

    public function scopePubliclyAvailable($query)
    {
        $today = now()->toDateString();

        $query
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

        if (self::supportsSubscriptionColumns()) {
            $query->where(function ($query) use ($today) {
                $query
                    ->where(function ($query) use ($today) {
                        $query
                            ->where('subscription_status', self::SUBSCRIPTION_TRIALING)
                            ->whereNotNull('trial_ends_at')
                            ->whereDate('trial_ends_at', '>=', $today);
                    })
                    ->orWhere(function ($query) use ($today) {
                        $query
                            ->where(function ($query) {
                                $query
                                    ->whereNull('subscription_status')
                                    ->orWhere('subscription_status', self::SUBSCRIPTION_ACTIVE);
                            })
                            ->where(function ($query) use ($today) {
                                $query
                                    ->whereNull('subscription_ends_at')
                                    ->orWhereDate('subscription_ends_at', '>=', $today);
                            });
                    });
            });
        }

        return $query;
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
            '--store-secondary-color: ' . $this->themeBackgroundColor(),
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

        if ($this->isFashionStore()) {
            return [
                'Mujer',
                'Hombre',
                'Zapatos',
                'Bolsos',
                'Accesorios',
                'Chaquetas',
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

    public function paymentAccounts()
    {
        return $this->hasMany(StorePaymentAccount::class);
    }

    public function aiCreditTransactions(): HasMany
    {
        return $this->hasMany(AiCreditTransaction::class);
    }

    public function mercadoPagoAccount()
    {
        return $this->hasOne(StorePaymentAccount::class)
            ->where('provider', StorePaymentAccount::PROVIDER_MERCADOPAGO);
    }

    public function categories()
    {
        return $this->hasMany(StoreCategory::class);
    }
}
