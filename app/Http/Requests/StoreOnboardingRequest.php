<?php

namespace App\Http\Requests;

use App\Models\Store;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOnboardingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $store = $this->user()?->store ?? $this->user()?->stores()->first();
        $phone = preg_replace('/\D+/', '', (string) $this->input('whatsapp')) ?: '';

        if (strlen($phone) === 10 && str_starts_with($phone, '3')) {
            $phone = '57'.$phone;
        }

        $this->merge([
            'business_type' => $store?->business_type ?: 'store',
            'whatsapp' => $phone,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'business_type' => ['required', Rule::in(array_keys(Store::businessTypeOptions()))],
            'whatsapp' => ['required', 'regex:/^573\d{9}$/'],
            'location' => ['nullable', 'string', 'max:255'],
            'shop_copy' => ['nullable', 'string', 'max:320'],
            'brand_color' => ['nullable', 'regex:/^#?(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'logo_image' => ['nullable', 'image', 'max:4096'],
        ];
    }

    public function onboardingData(): array
    {
        $brandColor = trim((string) $this->validated('brand_color'));

        if ($brandColor !== '' && ! str_starts_with($brandColor, '#')) {
            $brandColor = '#' . $brandColor;
        }

        return [
            'name' => $this->validated('name'),
            'business_type' => $this->validated('business_type'),
            'whatsapp' => $this->validated('whatsapp'),
            'location' => $this->validated('location'),
            'shop_copy' => $this->validated('shop_copy'),
            'brand_color' => $brandColor ?: '#ff6b00',
            'text_color' => Store::automaticTextColorFor('#ffffff'),
            'background_color' => '#ffffff',
        ];
    }
}
