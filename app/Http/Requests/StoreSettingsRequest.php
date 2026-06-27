<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesStoreProfile;
use App\Models\Store;
use Illuminate\Foundation\Http\FormRequest;

class StoreSettingsRequest extends FormRequest
{
    use ValidatesStoreProfile;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'subdomain' => Store::normalizeSubdomain($this->input('subdomain')),
            'custom_domain' => Store::normalizeCustomDomain($this->input('custom_domain')),
        ]);
    }

    public function rules(): array
    {
        return $this->storeProfileRules();
    }

    public function settingsData(): array
    {
        return $this->storeProfileData([
            'name',
            'business_type',
            'subdomain',
            'custom_domain',
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
            'require_terms_acceptance',
            'terms_title',
            'terms_content',
            'terms_url',
            'terms_version',
        ]);
    }
}
