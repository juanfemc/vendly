<?php

namespace App\Services;

use App\Models\StorePaymentAccount;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class MercadoPagoOAuthService
{
    private const AUTHORIZATION_URL = 'https://auth.mercadopago.com/authorization';
    private const TOKEN_URL = 'https://api.mercadopago.com/oauth/token';

    public function isConfigured(): bool
    {
        return filled($this->clientId())
            && filled($this->clientSecret())
            && filled($this->redirectUri());
    }

    public function authorizationUrl(string $state): string
    {
        return self::AUTHORIZATION_URL . '?' . http_build_query([
            'client_id' => $this->clientId(),
            'response_type' => 'code',
            'platform_id' => 'mp',
            'state' => $state,
            'redirect_uri' => $this->redirectUri(),
        ]);
    }

    public function exchangeAuthorizationCode(string $code): Response
    {
        return Http::asJson()
            ->acceptJson()
            ->post(self::TOKEN_URL, [
                'client_id' => $this->clientId(),
                'client_secret' => $this->clientSecret(),
                'code' => $code,
                'grant_type' => 'authorization_code',
                'redirect_uri' => $this->redirectUri(),
                'test_token' => $this->testToken() ? 'true' : 'false',
            ]);
    }

    public function refreshAccount(StorePaymentAccount $account): bool
    {
        if (! $this->isConfigured() || blank($account->refresh_token)) {
            return false;
        }

        try {
            $response = Http::asJson()
                ->acceptJson()
                ->post(self::TOKEN_URL, [
                    'client_id' => $this->clientId(),
                    'client_secret' => $this->clientSecret(),
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $account->refresh_token,
                ]);
        } catch (ConnectionException) {
            return false;
        }

        if ($response->failed()) {
            return false;
        }

        $payload = $response->json();
        $expiresIn = (int) ($payload['expires_in'] ?? 0);
        $accessToken = $payload['access_token'] ?? null;

        if (! is_string($accessToken) || trim($accessToken) === '' || $expiresIn <= 0) {
            return false;
        }

        $account->update([
            'access_token' => $accessToken,
            'refresh_token' => $payload['refresh_token'] ?? $account->refresh_token,
            'public_key' => $payload['public_key'] ?? $account->public_key,
            'provider_user_id' => isset($payload['user_id']) ? (string) $payload['user_id'] : $account->provider_user_id,
            'expires_at' => now()->addSeconds($expiresIn),
            'status' => StorePaymentAccount::STATUS_CONNECTED,
            'disconnected_at' => null,
        ]);

        return $account->refresh()->isConnected();
    }

    public function usableAccount(?StorePaymentAccount $account): ?StorePaymentAccount
    {
        if (! $account) {
            return null;
        }

        if ($account->isConnected()) {
            return $account;
        }

        if ($account->status !== StorePaymentAccount::STATUS_CONNECTED
            || ! $account->expires_at
            || $account->expires_at->isFuture()
        ) {
            return null;
        }

        return $this->refreshAccount($account) ? $account->refresh() : null;
    }

    public function redirectUri(): ?string
    {
        return config('services.mercadopago.redirect_uri') ?: route('admin.payments.mercadopago.callback');
    }

    private function clientId(): ?string
    {
        return config('services.mercadopago.client_id');
    }

    private function clientSecret(): ?string
    {
        return config('services.mercadopago.client_secret');
    }

    private function testToken(): bool
    {
        return (bool) config('services.mercadopago.test_token', false);
    }
}
