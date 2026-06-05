<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesStoreProfile;
use App\Models\Store;
use App\Services\StoreSlugService;
use App\Support\StoreTemplateCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreWithUserRequest extends FormRequest
{
    use ValidatesStoreProfile;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'template_key' => null,
            'slug' => app(StoreSlugService::class)->normalize($this->input('slug')),
            'subdomain' => Store::normalizeSubdomain($this->input('subdomain')),
            'custom_domain' => Store::normalizeCustomDomain($this->input('custom_domain')),
        ]);
    }

    public function rules(): array
    {
        return array_merge($this->storeProfileRules(), [
            'user_name' => ['required', 'string', 'max:255'],
            'user_email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'active_starts_at' => ['nullable', 'date'],
            'active_duration_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'slug' => app(StoreSlugService::class)->rules(),
            'template_key' => ['nullable', Rule::in(array_keys(StoreTemplateCatalog::all()))],
        ]);
    }

    public function userData(): array
    {
        return [
            'name' => $this->validated('user_name'),
            'email' => $this->validated('user_email'),
            'role' => 'store',
            'is_active' => true,
        ];
    }

    public function storeData(): array
    {
        return $this->storeProfileData([
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
