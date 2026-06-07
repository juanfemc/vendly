<?php

namespace App\Services;

use Closure;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class WhatsAppPhoneVerificationService
{
    public function __construct(private WhatsAppCloudApiService $whatsApp) {}

    public function isRequired(): bool
    {
        return (bool) config('services.whatsapp.require_phone_verification');
    }

    public function send(string $phone, ?Closure $canSend = null, ?string $requesterKey = null): string
    {
        $startedAt = hrtime(true);
        $this->ensureAvailable();
        $phone = $this->whatsApp->normalizePhone($phone);
        $lock = Cache::lock($this->lockKey($phone), 60);

        try {
            $token = $lock->block(5, function () use ($phone, $canSend, $requesterKey) {
                $rateKey = $this->sendRateKey($phone, $requesterKey);
                $phoneRateKey = $this->phoneSendRateKey($phone);

                if (RateLimiter::tooManyAttempts($rateKey, 3)) {
                    throw ValidationException::withMessages([
                        'whatsapp' => 'Solicitaste demasiados codigos. Intenta nuevamente mas tarde.',
                    ]);
                }

                if (RateLimiter::tooManyAttempts($phoneRateKey, 8)) {
                    throw ValidationException::withMessages([
                        'whatsapp' => 'Solicitaste demasiados codigos. Intenta nuevamente mas tarde.',
                    ]);
                }

                RateLimiter::hit($rateKey, 3600);
                RateLimiter::hit($phoneRateKey, 3600);

                if ($canSend && $canSend() === false) {
                    return Str::random(64);
                }

                $code = (string) random_int(100000, 999999);

                try {
                    $this->whatsApp->sendTemplate(
                        $phone,
                        (string) config('services.whatsapp.phone_verification_template'),
                        [$code],
                    );
                } catch (Throwable $exception) {
                    report($exception);

                    throw ValidationException::withMessages([
                        'whatsapp' => 'No pudimos enviar el codigo por WhatsApp. Intenta nuevamente en unos minutos.',
                    ]);
                }

                $token = Str::random(64);
                $expiresAt = now()->addMinutes(10);

                Cache::put($this->cacheKey($phone, $token), [
                    'hash' => $this->codeHash($code),
                    'expires_at' => $expiresAt->getTimestamp(),
                ], $expiresAt);

                return $token;
            });

            $this->waitForMinimumResponseTime($startedAt);

            return $token;
        } catch (LockTimeoutException) {
            throw ValidationException::withMessages([
                'whatsapp' => 'Ya estamos procesando una solicitud con este numero. Intenta nuevamente en unos segundos.',
            ]);
        }
    }

    public function runVerified(string $phone, ?string $token, ?string $code, Closure $callback): mixed
    {
        $phone = $this->whatsApp->normalizePhone($phone);
        $lock = Cache::lock($this->lockKey($phone), 60);

        try {
            return $lock->block(5, function () use ($phone, $token, $code, $callback) {
                if ($this->isRequired()) {
                    $this->ensureAvailable();
                    $codeHash = $this->assertValidCode($phone, $token, $code);
                    $this->consumeCode($phone, (string) $token);
                }

                try {
                    return $callback();
                } catch (Throwable $exception) {
                    if (isset($codeHash)) {
                        $this->restoreCode($phone, (string) $token, $codeHash);
                    }

                    throw $exception;
                }
            });
        } catch (LockTimeoutException) {
            throw ValidationException::withMessages([
                'whatsapp' => 'Ya estamos procesando un registro con este numero. Intenta nuevamente en unos segundos.',
            ]);
        }
    }

    private function assertValidCode(string $phone, ?string $token, ?string $code): array
    {
        $token = (string) $token;
        $cacheKey = $this->cacheKey($phone, $token);
        $attemptKey = $this->attemptKey($phone, $token);
        $expected = Cache::get($cacheKey);
        $provided = $this->codeHash(trim((string) $code));

        if (is_array($expected)
            && is_string($expected['hash'] ?? null)
            && is_int($expected['expires_at'] ?? null)
            && $expected['expires_at'] > now()->getTimestamp()
            && hash_equals($expected['hash'], $provided)) {
            return $expected;
        }

        RateLimiter::hit($attemptKey, 600);

        if (RateLimiter::tooManyAttempts($attemptKey, 5)) {
            Cache::forget($cacheKey);
        }

        throw ValidationException::withMessages([
            'whatsapp_verification_code' => 'El codigo es incorrecto, vencio o alcanzo el limite de intentos. Solicita uno nuevo.',
        ]);
    }

    private function consumeCode(string $phone, string $token): void
    {
        Cache::forget($this->cacheKey($phone, $token));
        RateLimiter::clear($this->attemptKey($phone, $token));
    }

    private function restoreCode(string $phone, string $token, array $payload): void
    {
        $expiresAt = (int) ($payload['expires_at'] ?? 0);

        if ($expiresAt <= now()->getTimestamp()) {
            return;
        }

        try {
            $expiration = now()->setTimestamp($expiresAt);
            Cache::put($this->cacheKey($phone, $token), $payload, $expiration);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function ensureAvailable(): void
    {
        if (! $this->isRequired()) {
            return;
        }

        if (! $this->whatsApp->isConfigured() || blank(config('services.whatsapp.phone_verification_template'))) {
            throw ValidationException::withMessages([
                'whatsapp' => 'La verificacion por WhatsApp no esta disponible temporalmente. Intenta nuevamente mas tarde.',
            ]);
        }
    }

    private function cacheKey(string $phone, string $token): string
    {
        return 'whatsapp-verification:'.hash('sha256', $phone.'|'.$token);
    }

    private function attemptKey(string $phone, string $token): string
    {
        return 'whatsapp-verification-attempts:'.hash('sha256', $phone.'|'.$token);
    }

    private function sendRateKey(string $phone, ?string $requesterKey): string
    {
        return 'whatsapp-verification-send:'.hash('sha256', $phone.'|'.($requesterKey ?: 'system'));
    }

    private function phoneSendRateKey(string $phone): string
    {
        return 'whatsapp-verification-phone-send:'.hash('sha256', $phone);
    }

    private function lockKey(string $phone): string
    {
        return 'trial-signup-lock:'.hash('sha256', $phone);
    }

    private function codeHash(string $code): string
    {
        return hash_hmac('sha256', $code, (string) config('app.key'));
    }

    private function waitForMinimumResponseTime(int $startedAt): void
    {
        $minimumMilliseconds = max(0, (int) config('services.whatsapp.verification_min_response_ms', 0));
        $elapsedMicroseconds = (int) ((hrtime(true) - $startedAt) / 1000);
        $remainingMicroseconds = ($minimumMilliseconds * 1000) - $elapsedMicroseconds;

        if ($remainingMicroseconds > 0) {
            usleep($remainingMicroseconds);
        }
    }
}
