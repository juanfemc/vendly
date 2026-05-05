<?php

namespace App\Http\Requests\Concerns;

use App\Models\Store;
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
        $data['text_color'] = $this->normalizeBrandColor($data['text_color'] ?? null);
        $data['font_family'] = $data['font_family'] ?? 'system';
        $data['responsive_product_columns'] = (int) ($data['responsive_product_columns'] ?? 2);
        $data['show_hero_products_action'] = $this->boolean('show_hero_products_action', false);

        return $data;
    }

    private function normalizeBrandColor(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : '#' . ltrim($value, '#');
    }
}
