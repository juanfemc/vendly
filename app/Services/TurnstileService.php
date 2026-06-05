<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class TurnstileService
{
    public function siteKey(): ?string
    {
        $siteKey = config('services.turnstile.site_key');

        return filled($siteKey) ? (string) $siteKey : null;
    }

    public function isRequired(): bool
    {
        return (bool) config('services.turnstile.required');
    }

    public function isReady(): bool
    {
        return filled(config('services.turnstile.site_key'))
            && filled(config('services.turnstile.secret_key'))
            && filled(config('services.turnstile.verification_url'));
    }

    public function verify(?string $token, ?string $ip = null): void
    {
        if (! $this->shouldVerify()) {
            return;
        }

        if (! $this->isReady()) {
            throw ValidationException::withMessages([
                'turnstile_token' => 'La proteccion anti abuso no esta configurada. Intenta nuevamente mas tarde.',
            ]);
        }

        if (blank($token)) {
            throw ValidationException::withMessages([
                'turnstile_token' => 'Confirma que eres una persona para enviar el codigo.',
            ]);
        }

        $response = Http::asForm()
            ->timeout(8)
            ->post((string) config('services.turnstile.verification_url'), array_filter([
                'secret' => (string) config('services.turnstile.secret_key'),
                'response' => $token,
                'remoteip' => $ip,
            ]));

        if (! $response->ok() || $response->json('success') !== true) {
            throw ValidationException::withMessages([
                'turnstile_token' => 'No pudimos verificar la proteccion anti abuso. Intenta nuevamente.',
            ]);
        }
    }

    private function shouldVerify(): bool
    {
        return $this->isRequired() || filled(config('services.turnstile.site_key'));
    }
}
