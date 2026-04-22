<?php

namespace App\Http\Requests;

use App\Models\Store;
use Illuminate\Foundation\Http\FormRequest;

class StoreSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'business_type' => ['required', 'in:' . implode(',', array_keys(Store::businessTypeOptions()))],
            'whatsapp' => ['required', 'string', 'max:255'],
            'shop_copy' => ['nullable', 'string', 'max:1000'],
            'cover_image' => ['nullable', 'image', 'max:4096'],
            'logo_image' => ['nullable', 'image', 'max:4096'],
            'brand_color' => ['nullable', 'regex:/^#?(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'instagram_url' => ['nullable', 'url', 'max:255'],
            'facebook_url' => ['nullable', 'url', 'max:255'],
            'tiktok_url' => ['nullable', 'url', 'max:255'],
        ];
    }

    public function settingsData(): array
    {
        $data = $this->safe()->only([
            'name',
            'business_type',
            'whatsapp',
            'shop_copy',
            'brand_color',
            'instagram_url',
            'facebook_url',
            'tiktok_url',
        ]);

        $data['brand_color'] = $this->normalizeBrandColor($data['brand_color'] ?? null);

        return $data;
    }

    private function normalizeBrandColor(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : '#' . ltrim($value, '#');
    }
}
