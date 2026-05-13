<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\StorePaymentAccount;
use App\Services\MercadoPagoOAuthService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PaymentSettingsController extends Controller
{
    private const MERCADOPAGO_OAUTH_SESSION_KEY = 'mercadopago_oauth';

    public function index(): View
    {
        $store = $this->currentStoreOrFail();
        $mercadoPagoAccount = $store->mercadoPagoAccount()->first();

        return view('admin.payments.index', compact('store', 'mercadoPagoAccount'));
    }

    public function connectMercadoPago(MercadoPagoOAuthService $mercadoPago): RedirectResponse
    {
        $store = $this->currentStoreOrFail();

        if (! $mercadoPago->isConfigured()) {
            return redirect()
                ->route('admin.payments.index')
                ->with('error', 'Configura las credenciales OAuth de Mercado Pago antes de conectar cuentas.');
        }

        $state = Str::random(48);
        session()->put(self::MERCADOPAGO_OAUTH_SESSION_KEY, [
            'state' => $state,
            'store_id' => $store->id,
            'expires_at' => now()->addMinutes(10)->timestamp,
        ]);

        return redirect()->away($mercadoPago->authorizationUrl($state));
    }

    public function mercadoPagoCallback(Request $request, MercadoPagoOAuthService $mercadoPago): RedirectResponse
    {
        $store = $this->currentStoreOrFail();
        $oauthSession = session()->pull(self::MERCADOPAGO_OAUTH_SESSION_KEY);

        if ($request->filled('error')) {
            return redirect()
                ->route('admin.payments.index')
                ->with('error', 'Mercado Pago no autorizo la conexion.');
        }

        if (! $this->validOAuthSession($oauthSession, $request->query('state'), $store)) {
            return redirect()
                ->route('admin.payments.index')
                ->with('error', 'No pudimos validar la conexion con Mercado Pago. Intenta nuevamente.');
        }

        $code = trim((string) $request->query('code'));

        if ($code === '') {
            return redirect()
                ->route('admin.payments.index')
                ->with('error', 'Mercado Pago no devolvio un codigo de autorizacion valido.');
        }

        try {
            $response = $mercadoPago->exchangeAuthorizationCode($code);
        } catch (ConnectionException) {
            return redirect()
                ->route('admin.payments.index')
                ->with('error', 'No pudimos conectar Mercado Pago. Revisa la conexion e intenta nuevamente.');
        }

        if ($response->failed()) {
            return redirect()
                ->route('admin.payments.index')
                ->with('error', 'No pudimos conectar Mercado Pago. Revisa la configuracion e intenta nuevamente.');
        }

        $payload = $response->json();
        $expiresIn = (int) ($payload['expires_in'] ?? 0);
        $accessToken = $payload['access_token'] ?? null;

        if (! is_string($accessToken) || trim($accessToken) === '' || $expiresIn <= 0) {
            return redirect()
                ->route('admin.payments.index')
                ->with('error', 'Mercado Pago no devolvio credenciales validas. Intenta nuevamente.');
        }

        StorePaymentAccount::updateOrCreate(
            [
                'store_id' => $store->id,
                'provider' => StorePaymentAccount::PROVIDER_MERCADOPAGO,
            ],
            [
                'access_token' => $accessToken,
                'refresh_token' => $payload['refresh_token'] ?? null,
                'public_key' => $payload['public_key'] ?? null,
                'provider_user_id' => isset($payload['user_id']) ? (string) $payload['user_id'] : null,
                'expires_at' => now()->addSeconds($expiresIn),
                'connected_at' => now(),
                'disconnected_at' => null,
                'status' => StorePaymentAccount::STATUS_CONNECTED,
            ]
        );

        return redirect()
            ->route('admin.payments.index')
            ->with('success', 'Mercado Pago conectado correctamente.');
    }

    private function currentStoreOrFail(): Store
    {
        $store = auth()->user()?->store ?? auth()->user()?->stores()->first();

        abort_unless($store, 404);

        return $store;
    }

    private function validOAuthSession(mixed $oauthSession, mixed $state, Store $store): bool
    {
        if (! is_array($oauthSession) || ! is_string($state)) {
            return false;
        }

        if ((int) ($oauthSession['store_id'] ?? 0) !== (int) $store->id) {
            return false;
        }

        if ((int) ($oauthSession['expires_at'] ?? 0) < now()->timestamp) {
            return false;
        }

        return hash_equals((string) ($oauthSession['state'] ?? ''), $state);
    }
}
