<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesStoreProfile;
use Illuminate\Foundation\Http\FormRequest;

class StoreSettingsRequest extends FormRequest
{
    use ValidatesStoreProfile;

    public function authorize(): bool
    {
        return true;
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
            'whatsapp',
            'location',
            'business_hours',
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
        ]);
    }
}
