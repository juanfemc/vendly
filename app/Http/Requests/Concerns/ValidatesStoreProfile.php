<?php

namespace App\Http\Requests\Concerns;

use App\Models\ColombiaLocation;
use App\Models\Store;
use App\Support\BrandTheme;
use Illuminate\Validation\Rule;

trait ValidatesStoreProfile
{
    protected function storeProfileRules(): array
    {
        $store = $this->profileStore();
        $storeId = $store?->id;
        $subdomainRules = [
            'nullable',
            'string',
            'max:63',
            'regex:/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/',
            Rule::notIn(Store::reservedSubdomains()),
        ];

        if (Store::supportsSubdomainColumn()) {
            $subdomainRules[] = Rule::unique('stores', 'subdomain')->ignore($storeId);
        }

        $customDomainRules = [
            'nullable',
            'string',
            'max:253',
            'regex:/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/',
            Rule::notIn($this->reservedCustomDomains()),
            function (string $attribute, mixed $value, \Closure $fail) {
                $domain = Store::normalizeCustomDomain($value);
                $appHost = Store::normalizeCustomDomain(parse_url(config('app.url'), PHP_URL_HOST));

                if ($domain && $appHost && str_ends_with($domain, '.' . $appHost)) {
                    $fail('Usa el campo de subdominio para direcciones de ' . $appHost . '.');
                }
            },
        ];

        if (Store::supportsCustomDomainColumns()) {
            $customDomainRules[] = Rule::unique('stores', 'custom_domain')->ignore($storeId);
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'business_type' => ['required', 'in:' . implode(',', array_keys(Store::businessTypeOptions()))],
            'plan' => ['nullable', Rule::in(array_keys(Store::planOptions()))],
            'subdomain' => $subdomainRules,
            'custom_domain' => $customDomainRules,
            'custom_domain_status' => ['nullable', Rule::in([
                Store::CUSTOM_DOMAIN_PENDING,
                Store::CUSTOM_DOMAIN_VERIFIED,
                Store::CUSTOM_DOMAIN_FAILED,
            ])],
            'whatsapp' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'business_hours' => ['nullable', 'string', 'max:1000'],
            'announcement_items' => ['nullable', 'array', 'max:5'],
            'announcement_items.*.text' => ['nullable', 'string', 'max:140'],
            'free_shipping_minimum' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'shipping_methods' => ['nullable', 'array', 'max:5'],
            'shipping_methods.*.name' => ['nullable', 'string', 'max:80'],
            'shipping_methods.*.cost' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'local_delivery_area' => [
                'nullable',
                'string',
                'max:120',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (trim((string) $value) === '' || ! ColombiaLocation::hasCatalog()) {
                        return;
                    }

                    $exists = ColombiaLocation::query()
                        ->where('city_name', $value)
                        ->orWhere('city_code', $value)
                        ->exists();

                    if (! $exists) {
                        $fail('Selecciona una ciudad local valida.');
                    }
                },
            ],
            'local_delivery_city_code' => array_filter([
                'nullable',
                'string',
                'max:12',
                ColombiaLocation::hasCatalog()
                    ? Rule::exists('colombia_locations', 'city_code')
                    : null,
            ]),
            'local_delivery_cost' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'outside_delivery_cost' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'reservation_available_days' => ['nullable', 'array'],
            'reservation_available_days.*' => ['string', Rule::in(array_keys(Store::reservationDayOptions()))],
            'reservation_time_start' => ['nullable', 'date_format:H:i'],
            'reservation_time_end' => ['nullable', 'date_format:H:i', 'after:reservation_time_start'],
            'shop_copy' => ['nullable', 'string', 'max:1000'],
            'mission' => ['nullable', 'string', 'max:1000'],
            'vision' => ['nullable', 'string', 'max:1000'],
            'cover_image' => ['nullable', 'image', 'max:4096'],
            'logo_image' => ['nullable', 'image', 'max:4096'],
            'brand_color' => ['nullable', 'regex:/^#?(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'background_color' => ['nullable', 'regex:/^#?(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'text_color' => ['nullable', 'regex:/^#?(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'font_family' => ['nullable', Rule::in(array_keys(Store::fontFamilyOptions()))],
            'responsive_product_columns' => ['nullable', 'integer', 'in:1,2,3'],
            'show_hero_products_action' => ['nullable', 'boolean'],
            'instagram_url' => ['nullable', 'url', 'max:255'],
            'facebook_url' => ['nullable', 'url', 'max:255'],
            'tiktok_url' => ['nullable', 'url', 'max:255'],
        ];
    }

    protected function storeProfileData(array $fields): array
    {
        $data = $this->safe()->only($fields);
        $requestedPlan = array_key_exists('plan', $data);
        $effectivePlan = $data['plan']
            ?? $this->profileStore()?->plan
            ?? Store::PLAN_PRO;

        $data['brand_color'] = $this->normalizeBrandColor($data['brand_color'] ?? null);
        $data['background_color'] = $this->normalizeBrandColor($data['background_color'] ?? null);
        $data['text_color'] = Store::automaticTextColorFor($data['background_color']);
        $data['font_family'] = $data['font_family'] ?? 'system';
        if ($requestedPlan) {
            $data['plan'] = $effectivePlan;
        }
        if (Store::supportsSubdomainColumn()) {
            $data['subdomain'] = Store::normalizeSubdomain($data['subdomain'] ?? null);
        } else {
            unset($data['subdomain']);
        }

        if (Store::supportsCustomDomainColumns()) {
            $data['custom_domain'] = Store::normalizeCustomDomain($data['custom_domain'] ?? null);
            $this->applyCustomDomainState($data, $effectivePlan);
        } else {
            unset($data['custom_domain'], $data['custom_domain_status'], $data['custom_domain_verified_at']);
        }

        $data['responsive_product_columns'] = (int) ($data['responsive_product_columns'] ?? 2);
        $data['show_hero_products_action'] = $this->boolean('show_hero_products_action', false);

        if ($effectivePlan === Store::PLAN_BASIC) {
            if (Store::supportsSubdomainColumn()) {
                $data['subdomain'] = null;
            }
            if (Store::supportsCustomDomainColumns()) {
                $data['custom_domain'] = null;
                $data['custom_domain_status'] = Store::CUSTOM_DOMAIN_PENDING;
                $data['custom_domain_verified_at'] = null;
            }
            $data['brand_color'] = null;
            $data['background_color'] = null;
            $data['text_color'] = Store::automaticTextColorFor(null);
            $data['font_family'] = 'system';
            $data['responsive_product_columns'] = 2;
            $data['show_hero_products_action'] = false;

            if (Store::supportsCommercialNoticeColumns()) {
                $data['announcement_items'] = [];
                $data['free_shipping_minimum'] = null;
            } else {
                $this->forgetShippingData($data);
                unset($data['announcement_items'], $data['free_shipping_minimum']);
            }
        } elseif (! Store::supportsCommercialNoticeColumns()) {
            unset($data['announcement_items'], $data['free_shipping_minimum']);
        } else {
            $data['announcement_items'] = $this->cleanAnnouncementItems($data['announcement_items'] ?? []);
            $data['free_shipping_minimum'] = $this->filled('free_shipping_minimum')
                ? (float) $this->input('free_shipping_minimum')
                : null;
        }

        if (! Store::supportsShippingMethodsColumn()) {
            $this->forgetShippingData($data);
        } elseif ($effectivePlan !== Store::PLAN_BASIC) {
            $this->applyShippingData($data);
        } else {
            $this->clearShippingData($data);
        }

        if (! Store::supportsReservationScheduleColumns()) {
            unset($data['reservation_available_days'], $data['reservation_time_start'], $data['reservation_time_end']);

            return $data;
        }

        $data['reservation_available_days'] = $data['business_type'] === 'reservations'
            ? array_values($data['reservation_available_days'] ?? [])
            : null;
        $data['reservation_time_start'] = $data['business_type'] === 'reservations'
            ? ($data['reservation_time_start'] ?? null)
            : null;
        $data['reservation_time_end'] = $data['business_type'] === 'reservations'
            ? ($data['reservation_time_end'] ?? null)
            : null;

        return $data;
    }

    private function cleanAnnouncementItems(array $items): array
    {
        return collect($items)
            ->pluck('text')
            ->map(fn ($text) => trim((string) $text))
            ->filter()
            ->take(5)
            ->map(fn (string $text) => ['text' => $text])
            ->values()
            ->all();
    }

    private function applyShippingData(array &$data): void
    {
        $data['shipping_methods'] = collect($data['shipping_methods'] ?? [])
            ->map(function ($method) {
                $name = trim((string) ($method['name'] ?? ''));

                return $name === ''
                    ? null
                    : [
                        'name' => $name,
                        'cost' => max(0, (float) ($method['cost'] ?? 0)),
                    ];
            })
            ->filter()
            ->take(5)
            ->values()
            ->all();

        if (! Store::supportsLocalDeliveryColumns()) {
            $this->forgetLocalDeliveryData($data);

            return;
        }

        $deliveryCity = $this->resolveLocalDeliveryCity(
            $data['local_delivery_city_code'] ?? null,
            $data['local_delivery_area'] ?? null,
        );

        $data['local_delivery_area'] = $deliveryCity?->city_name
            ?: (trim((string) ($data['local_delivery_area'] ?? '')) ?: null);

        if (Store::supportsLocalDeliveryCityCodeColumn()) {
            $data['local_delivery_city_code'] = $deliveryCity?->city_code;
        } else {
            unset($data['local_delivery_city_code']);
        }

        $data['local_delivery_cost'] = $this->filled('local_delivery_cost')
            ? max(0, (float) $this->input('local_delivery_cost'))
            : null;
        $data['outside_delivery_cost'] = $this->filled('outside_delivery_cost')
            ? max(0, (float) $this->input('outside_delivery_cost'))
            : null;
    }

    private function clearShippingData(array &$data): void
    {
        $data['shipping_methods'] = [];

        if (! Store::supportsLocalDeliveryColumns()) {
            $this->forgetLocalDeliveryData($data);

            return;
        }

        $data['local_delivery_area'] = null;
        $data['local_delivery_cost'] = null;
        $data['outside_delivery_cost'] = null;

        if (Store::supportsLocalDeliveryCityCodeColumn()) {
            $data['local_delivery_city_code'] = null;
        } else {
            unset($data['local_delivery_city_code']);
        }
    }

    private function forgetShippingData(array &$data): void
    {
        unset($data['shipping_methods']);
        $this->forgetLocalDeliveryData($data);
    }

    private function forgetLocalDeliveryData(array &$data): void
    {
        unset(
            $data['local_delivery_area'],
            $data['local_delivery_city_code'],
            $data['local_delivery_cost'],
            $data['outside_delivery_cost'],
        );
    }

    private function resolveLocalDeliveryCity(?string $cityCode, ?string $cityName): ?ColombiaLocation
    {
        if (! ColombiaLocation::hasCatalog()) {
            return null;
        }

        $lookupValue = trim((string) ($cityCode ?: $cityName));

        if ($lookupValue === '') {
            return null;
        }

        return ColombiaLocation::query()
            ->where('city_code', $lookupValue)
            ->orWhere('city_name', $lookupValue)
            ->first();
    }

    private function applyCustomDomainState(array &$data, string $effectivePlan): void
    {
        $store = $this->profileStore();
        $domain = $data['custom_domain'] ?? null;
        $previousDomain = Store::normalizeCustomDomain($store?->custom_domain);
        $requestedStatus = $data['custom_domain_status'] ?? null;

        if ($effectivePlan !== Store::PLAN_PREMIUM) {
            $data['custom_domain'] = null;
            $data['custom_domain_status'] = Store::CUSTOM_DOMAIN_PENDING;
            $data['custom_domain_verified_at'] = null;

            return;
        }

        if ($domain === $previousDomain) {
            if (array_key_exists('custom_domain_status', $data)) {
                if ($domain && $requestedStatus === Store::CUSTOM_DOMAIN_VERIFIED) {
                    $data['custom_domain_verified_at'] = $store?->custom_domain_verified_at ?? now();
                } else {
                    $data['custom_domain_verified_at'] = null;
                }
            }

            return;
        }

        $data['custom_domain_status'] = Store::CUSTOM_DOMAIN_PENDING;
        $data['custom_domain_verified_at'] = null;
    }

    private function normalizeBrandColor(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : BrandTheme::normalizeColor($value);
    }

    private function reservedCustomDomains(): array
    {
        $appHost = Store::normalizeCustomDomain(parse_url(config('app.url'), PHP_URL_HOST));

        return array_values(array_filter([
            $appHost,
            $appHost ? 'www.' . preg_replace('/^www\./', '', $appHost) : null,
        ]));
    }

    private function profileStore(): ?Store
    {
        $routeStore = $this->route('store');

        if ($routeStore instanceof Store) {
            return $routeStore;
        }

        $user = $this->user();

        return $user?->store ?? $user?->stores()->first();
    }
}
