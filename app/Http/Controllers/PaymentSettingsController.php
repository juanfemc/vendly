<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\StorePaymentAccount;
use App\Services\MercadoPagoOAuthService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class PaymentSettingsController extends Controller
{
    private const MERCADOPAGO_OAUTH_SESSION_KEY = 'mercadopago_oauth';

    public function index(): View
    {
        $store = $this->currentStoreOrFail();

        abort_unless($store->allowsOnlinePayments(), 403);

        $mercadoPagoAccount = $store->mercadoPagoAccount()->first();
        $wompiAccount = $store->wompiAccount()->first();

        return view('admin.payments.index', compact('store', 'mercadoPagoAccount', 'wompiAccount'));
    }

    public function updateWompi(Request $request): RedirectResponse
    {
        $store = $this->currentStoreOrFail();

        abort_unless($store->allowsOnlinePayments(), 403);

        $current = $store->wompiAccount()->first();

        $validator = Validator::make($request->all(), [
            'enabled' => ['nullable', 'boolean'],
            'mode' => ['required', 'in:' . StorePaymentAccount::MODE_SANDBOX . ',' . StorePaymentAccount::MODE_PRODUCTION],
            'public_key' => ['nullable', 'string', 'max:1000'],
            'private_key' => ['nullable', 'string', 'max:1000'],
            'events_secret' => ['nullable', 'string', 'max:1000'],
            'integrity_secret' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput($request->except(['private_key', 'events_secret', 'integrity_secret']));
        }

        $validated = $validator->validated();

        foreach (['public_key', 'private_key', 'events_secret', 'integrity_secret'] as $field) {
            if (isset($validated[$field]) && is_string($validated[$field])) {
                $validated[$field] = trim($validated[$field]);
            }
        }

        $enabled = $request->boolean('enabled');

        if ($enabled) {
            foreach (['public_key', 'private_key', 'events_secret', 'integrity_secret'] as $field) {
                if (blank($validated[$field] ?? null) && blank($current?->{$field})) {
                    return back()
                        ->withErrors([$field => 'Completa todas las credenciales de Wompi para activarlo.'])
                        ->withInput($request->except(['private_key', 'events_secret', 'integrity_secret']));
                }
            }
        }

        $now = now();

        StorePaymentAccount::updateOrCreate(
            [
                'store_id' => $store->id,
                'provider' => StorePaymentAccount::PROVIDER_WOMPI,
            ],
            [
                'public_key' => filled($validated['public_key'] ?? null) ? $validated['public_key'] : $current?->public_key,
                'private_key' => filled($validated['private_key'] ?? null) ? $validated['private_key'] : $current?->private_key,
                'events_secret' => filled($validated['events_secret'] ?? null) ? $validated['events_secret'] : $current?->events_secret,
                'integrity_secret' => filled($validated['integrity_secret'] ?? null) ? $validated['integrity_secret'] : $current?->integrity_secret,
                'mode' => $validated['mode'],
                'connected_at' => $enabled ? ($current?->connected_at ?? $now) : $current?->connected_at,
                'disconnected_at' => $enabled ? null : $now,
                'status' => $enabled ? StorePaymentAccount::STATUS_CONNECTED : StorePaymentAccount::STATUS_DISCONNECTED,
            ]
        );

        return redirect()
            ->route('admin.payments.index')
            ->with('success', $enabled ? 'Wompi configurado correctamente.' : 'Wompi fue desactivado.');
    }

    public function connectMercadoPago(MercadoPagoOAuthService $mercadoPago): RedirectResponse
    {
        $store = $this->currentStoreOrFail();

        abort_unless($store->allowsOnlinePayments(), 403);

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

        abort_unless($store->allowsOnlinePayments(), 403);

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
