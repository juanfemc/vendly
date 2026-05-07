<?php

namespace App\Http\Requests\Concerns;

use App\Models\Store;
use App\Support\BrandTheme;
use Illuminate\Validation\Rule;

trait ValidatesStoreProfile
{
    protected function storeProfileRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'business_type' => ['required', 'in:' . implode(',', array_keys(Store::businessTypeOptions()))],
            'whatsapp' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'business_hours' => ['nullable', 'string', 'max:1000'],
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

        $data['brand_color'] = $this->normalizeBrandColor($data['brand_color'] ?? null);
        $data['background_color'] = $this->normalizeBrandColor($data['background_color'] ?? null);
        $data['text_color'] = Store::automaticTextColorFor($data['background_color']);
        $data['font_family'] = $data['font_family'] ?? 'system';
        $data['responsive_product_columns'] = (int) ($data['responsive_product_columns'] ?? 2);
        $data['show_hero_products_action'] = $this->boolean('show_hero_products_action', false);

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

    private function normalizeBrandColor(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : BrandTheme::normalizeColor($value);
    }
}
