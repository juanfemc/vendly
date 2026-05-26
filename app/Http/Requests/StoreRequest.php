<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesStoreProfile;
use App\Models\Store;
use App\Services\StoreSlugService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
{
    use ValidatesStoreProfile;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => app(StoreSlugService::class)->normalize($this->input('slug')),
            'subdomain' => Store::normalizeSubdomain($this->input('subdomain')),
            'custom_domain' => Store::normalizeCustomDomain($this->input('custom_domain')),
        ]);
    }

    public function rules(): array
    {
        $store = $this->route('store');
        $storeId = $store instanceof Store ? $store->id : null;

        return array_merge($this->storeProfileRules(), [
            'user_id' => [
                'required',
                Rule::exists('users', 'id')->where('role', 'store'),
                Rule::unique('stores', 'user_id')->ignore($storeId),
            ],
            'slug' => app(StoreSlugService::class)->rules($storeId),
        ]);
    }

    public function storeData(): array
    {
        return $this->storeProfileData([
            'user_id',
            'name',
            'business_type',
            'plan',
            'slug',
            'subdomain',
            'custom_domain',
            'custom_domain_status',
            'whatsapp',
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
        ]);
    }
}
