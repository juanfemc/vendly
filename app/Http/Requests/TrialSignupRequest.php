<?php

namespace App\Http\Requests;

use App\Models\Store;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class TrialSignupRequest extends FormRequest
{
    public const WHATSAPP_CONSENT_TEXT = 'Acepto recibir por WhatsApp la bienvenida y mensajes relacionados con la activacion de mi tienda.';

    protected function prepareForValidation(): void
    {
        $phone = preg_replace('/\D+/', '', (string) $this->input('whatsapp')) ?: '';

        if (strlen($phone) === 10 && str_starts_with($phone, '3')) {
            $phone = '57'.$phone;
        }

        $this->merge(['whatsapp' => $phone]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_name' => ['required', 'string', 'max:255'],
            'user_email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'store_name' => ['required', 'string', 'max:255'],
            'whatsapp' => ['required', 'regex:/^573\d{9}$/'],
            'location' => ['nullable', 'string', 'max:255'],
            'whatsapp_consent' => ['accepted'],
            'whatsapp_verification_code' => ['nullable', 'digits:6'],
            'whatsapp_verification_token' => ['nullable', 'string', 'size:64'],
        ];
    }

    public function userData(): array
    {
        return [
            'name' => $this->validated('user_name'),
            'email' => $this->validated('user_email'),
            'role' => 'store',
            'is_active' => true,
            'active_starts_at' => now()->toDateString(),
            'active_ends_at' => null,
        ];
    }

    public function storeData(string $slug): array
    {
        return [
            'name' => $this->validated('store_name'),
            'business_type' => 'store',
            'plan' => Store::PLAN_PREMIUM,
            'slug' => $slug,
            'whatsapp' => $this->validated('whatsapp'),
            'whatsapp_consent_at' => now(),
            'whatsapp_consent_version' => config('services.whatsapp.consent_version', 'registration_v1'),
            'whatsapp_consent_text' => self::WHATSAPP_CONSENT_TEXT,
            'whatsapp_consent_source' => 'trial_signup',
            'whatsapp_consent_ip_hash' => hash_hmac(
                'sha256',
                (string) ($this->ip() ?: 'unknown'),
                (string) config('app.key'),
            ),
            'location' => $this->validated('location'),
            'brand_color' => '#ff6b00',
            'background_color' => '#ffffff',
            'text_color' => '#111111',
            'font_family' => 'system',
            'responsive_product_columns' => 2,
            'is_active' => true,
        ];
    }
}
