<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesStoreProfile;
use App\Models\Store;
use App\Models\User;
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
            'subscription_status' => ['nullable', Rule::in(array_keys(Store::subscriptionStatusOptions()))],
            'trial_ends_at' => ['nullable', 'date'],
            'subscription_ends_at' => ['nullable', 'date'],
        ]);
    }

    public function storeData(): array
    {
        $data = $this->storeProfileData([
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

        if (Store::supportsSubscriptionColumns()) {
            $store = $this->route('store');
            $sameOwner = $store instanceof Store && (int) $this->input('user_id') === (int) $store->user_id;
            $currentSubscriptionEndsAt = $sameOwner ? $store->subscription_ends_at : null;
            $selectedUser = $sameOwner
                ? $store->user
                : User::find($this->input('user_id'));
            $userActiveEndsAt = $selectedUser?->active_ends_at;

            $data['subscription_status'] = $this->input('subscription_status') ?: Store::SUBSCRIPTION_ACTIVE;
            $data['trial_ends_at'] = $this->filled('trial_ends_at') ? $this->date('trial_ends_at')->endOfDay() : null;
            $data['subscription_ends_at'] = $this->filled('subscription_ends_at')
                ? $this->date('subscription_ends_at')->endOfDay()
                : $currentSubscriptionEndsAt;

            if ($data['subscription_status'] === Store::SUBSCRIPTION_TRIALING && ! $this->filled('trial_ends_at')) {
                $data['trial_ends_at'] = now()->addDays(Store::TRIAL_DAYS);
            }

            if ($data['subscription_status'] === Store::SUBSCRIPTION_ACTIVE && ! $data['subscription_ends_at']) {
                $data['subscription_ends_at'] = $userActiveEndsAt?->copy()->endOfDay();
            }

            if ($data['subscription_status'] === Store::SUBSCRIPTION_TRIALING) {
                $data['trial_starts_at'] = $this->route('store')?->trial_starts_at ?? now();
            }

            if ($data['subscription_status'] !== Store::SUBSCRIPTION_TRIALING) {
                $data['trial_starts_at'] = null;
                $data['trial_ends_at'] = null;
            }
        }

        return $data;
    }
}
