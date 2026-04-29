<?php

namespace App\Http\Requests;

use App\Models\Store;
use App\Services\StoreSlugService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => app(StoreSlugService::class)->normalize($this->input('slug')),
        ]);
    }

    public function rules(): array
    {
        $store = $this->route('store');
        $storeId = $store instanceof Store ? $store->id : null;

        return [
            'user_id' => [
                'required',
                Rule::exists('users', 'id')->where('role', 'store'),
                Rule::unique('stores', 'user_id')->ignore($storeId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'business_type' => ['required', 'in:' . implode(',', array_keys(Store::businessTypeOptions()))],
            'slug' => app(StoreSlugService::class)->rules($storeId),
            'whatsapp' => ['required', 'string', 'max:255'],
            'shop_copy' => ['nullable', 'string', 'max:1000'],
            'cover_image' => ['nullable', 'image', 'max:4096'],
            'logo_image' => ['nullable', 'image', 'max:4096'],
            'brand_color' => ['nullable', 'regex:/^#?(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'responsive_product_columns' => ['nullable', 'integer', 'in:1,2,3'],
            'show_hero_products_action' => ['nullable', 'boolean'],
            'instagram_url' => ['nullable', 'url', 'max:255'],
            'facebook_url' => ['nullable', 'url', 'max:255'],
            'tiktok_url' => ['nullable', 'url', 'max:255'],
        ];
    }

    public function storeData(): array
    {
        $data = $this->safe()->only([
            'user_id',
            'name',
            'business_type',
            'slug',
            'whatsapp',
            'shop_copy',
            'brand_color',
            'responsive_product_columns',
            'show_hero_products_action',
            'instagram_url',
            'facebook_url',
            'tiktok_url',
        ]);

        $data['brand_color'] = $this->normalizeBrandColor($data['brand_color'] ?? null);
        $data['responsive_product_columns'] = (int) ($data['responsive_product_columns'] ?? 2);
        $data['show_hero_products_action'] = $this->boolean('show_hero_products_action', true);

        return $data;
    }

    private function normalizeBrandColor(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : '#' . ltrim($value, '#');
    }
}
