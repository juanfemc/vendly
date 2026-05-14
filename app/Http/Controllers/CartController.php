<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\StorePaymentAccount;
use App\Services\AdminUpdateService;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\MercadoPagoCheckoutService;
use App\Services\MercadoPagoOAuthService;
use App\Services\StorefrontUrlService;
use App\Services\WhatsAppOrderMessageBuilder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CartController extends Controller
{
    public function __construct(
        private CartService $cartService,
        private CheckoutService $checkoutService,
        private WhatsAppOrderMessageBuilder $whatsAppOrderMessageBuilder,
        private AdminUpdateService $adminUpdateService,
        private MercadoPagoCheckoutService $mercadoPagoCheckoutService,
        private MercadoPagoOAuthService $mercadoPagoOAuthService,
        private StorefrontUrlService $storefrontUrls,
    ) {
    }

    public function add(Request $request, $id)
    {
        $product = Product::with('store.user')->findOrFail($id);
        [$cart, $message] = $this->cartService->addProduct(
            $product,
            $this->cartService->requestedQuantity($request),
            $this->cartService->requestedOptions($request, $product),
        );

        if ($message) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'cart_count' => collect($cart)->sum('quantity'),
                ], 422);
            }

            return back()->with('error', $message);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Producto agregado',
                'cart_count' => collect($cart)->sum('quantity'),
            ]);
        }

        return back()->with('success', 'Producto agregado');
    }

    public function buyNow(Request $request, $id)
    {
        $product = Product::with('store.user')->findOrFail($id);
        [, $message] = $this->cartService->addProduct(
            $product,
            $this->cartService->requestedQuantity($request),
            $this->cartService->requestedOptions($request, $product),
        );

        if ($message) {
            return back()->with('error', $message);
        }

        return redirect()->route('cart.index', ['store' => $product->store?->slug])->with('success', 'Producto agregado');
    }

    public function index(Request $request)
    {
        $store = $this->cartService->storeForRequest($request);
        $cart = $this->cartService->cartForStore($store);
        $total = $this->cartService->total($cart);
        $mercadoPagoAccount = $store?->mercadoPagoAccount()->first();
        $mercadoPagoAvailable = ($store?->allowsOnlinePayments() ?? false)
            && ($mercadoPagoAccount?->isConnected() ?? false);

        return view('cart_checkout', compact('cart', 'store', 'total', 'mercadoPagoAvailable'));
    }

    public function updateItem(Request $request, $id)
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        [$cart, $message] = $this->cartService->updateItemQuantitySafely((string) $id, (int) $validated['quantity']);

        if ($message) {
            return response()->json(['message' => $message], $cart === null ? 404 : 422);
        }

        return response()->json($this->cartService->responsePayload($cart, (string) $id, 'Cantidad actualizada'));
    }

    public function removeItem($id)
    {
        $cart = $this->cartService->removeItem((string) $id);

        if ($cart === null) {
            return response()->json(['message' => 'Producto no encontrado en el carrito.'], 404);
        }

        return response()->json($this->cartService->responsePayload($cart, null, 'Producto eliminado del carrito'));
    }

    public function clear(Request $request)
    {
        $store = $this->cartService->storeForRequest($request);

        $this->cartService->forgetCartForStore($store);

        return response()->json([
            'message' => 'Carrito vaciado',
            'cart_count' => 0,
            'cart_is_empty' => true,
            'total' => 0,
            'item_total' => null,
        ]);
    }

    public function whatsappFromCart(CheckoutRequest $request)
    {
        $checkout = $this->checkoutContext($request, requiresWhatsApp: true);

        if ($checkout instanceof RedirectResponse) {
            return $checkout;
        }

        ['validated' => $validated, 'store' => $store, 'cart' => $cart] = $checkout;
        $order = $this->checkoutService->createOrder($store, $cart, $validated);
        $order->load(['items.product', 'store']);

        $this->adminUpdateService->record(
            $store->isReservationStore() ? 'Reserva nueva' : 'Pedido nuevo',
            ($store->isReservationStore() ? 'Reserva #' : 'Pedido #') . $order->id . ' en ' . $store->name . ' por ' . $order->customer_name,
            'pedido',
            '/admin/orders'
        );

        $url = $this->whatsAppOrderMessageBuilder->url($order);

        if (! $url) {
            return redirect()->route('cart.index', ['store' => $store->slug])->with('error', 'La tienda no tiene un WhatsApp configurado.');
        }

        $this->cartService->forgetCartForStore($store);

        return redirect($url);
    }

    public function mercadoPagoFromCart(CheckoutRequest $request)
    {
        $checkout = $this->checkoutContext($request);

        if ($checkout instanceof RedirectResponse) {
            return $checkout;
        }

        ['validated' => $validated, 'store' => $store, 'cart' => $cart] = $checkout;

        if (! $store->allowsOnlinePayments()) {
            return redirect()->route('cart.index', ['store' => $store->slug])->with('error', 'Los pagos en linea estan disponibles solo en el plan Premium.');
        }

        $mercadoPagoAccount = $store->mercadoPagoAccount()->first();

        if (! ($mercadoPagoAccount?->isConnected() ?? false)) {
            return redirect()->route('cart.index', ['store' => $store->slug])->with('error', 'Esta tienda todavia no tiene Mercado Pago activo.');
        }

        $paymentData = [
            'payment_method' => Order::PAYMENT_METHOD_MERCADOPAGO,
            'payment_provider' => StorePaymentAccount::PROVIDER_MERCADOPAGO,
            'payment_status' => Order::PAYMENT_STATUS_PENDING,
        ];

        if (Order::supportsPaymentExpirationColumn()) {
            $paymentData['payment_expires_at'] = now()->addMinutes(max(1, (int) config('services.mercadopago.payment_expiration_minutes', 60)));
        }

        $order = $this->checkoutService->createOrder($store, $cart, $validated, $paymentData);

        try {
            $preference = $this->mercadoPagoCheckoutService->createPreference($mercadoPagoAccount, $order);
        } catch (ConnectionException|RequestException) {
            $this->checkoutService->deleteOrderAndReleaseStock($order);

            return redirect()->route('cart.index', ['store' => $store->slug])
                ->withInput()
                ->with('error', 'No se pudo iniciar el pago con Mercado Pago. Intenta nuevamente.');
        }

        if (empty($preference['redirect_url'])) {
            $this->checkoutService->deleteOrderAndReleaseStock($order);

            return redirect()->route('cart.index', ['store' => $store->slug])
                ->withInput()
                ->with('error', 'Mercado Pago no devolvio una URL de pago. Intenta nuevamente.');
        }

        $order->update([
            'payment_provider_reference' => $preference['id'],
            'payment_preference_id' => $preference['id'],
        ]);

        $this->cartService->forgetCartForStore($store);

        return redirect()->away($preference['redirect_url']);
    }

    public function mercadoPagoReturn(Order $order, string $result)
    {
        $paymentConfirmationPending = false;

        if ($result === 'success' && request()->filled('payment_id')) {
            try {
                $this->syncMercadoPagoPayment($order, (string) request('payment_id'));
            } catch (ConnectionException|RequestException) {
                $paymentConfirmationPending = true;
            }
        }

        $order->refresh()->loadMissing('store');
        $store = $order->store;
        $storeUrl = $store ? $this->storefrontUrls->home($store, request()) : url('/');

        return view('payment_return', compact('order', 'store', 'storeUrl', 'result', 'paymentConfirmationPending'));
    }

    public function mercadoPagoWebhook(Request $request): Response
    {
        if (! $this->hasValidMercadoPagoSignature($request)) {
            return response('Invalid signature', 401);
        }

        $type = $request->input('type') ?: $request->query('type');
        $paymentId = $request->input('data.id') ?: $request->query('data_id') ?: $request->query('id');

        if ($type !== 'payment' || ! $paymentId) {
            return response('Ignored', 200);
        }

        try {
            $sync = $this->syncMercadoPagoWebhookPayment($request, (string) $paymentId);
        } catch (ConnectionException|RequestException) {
            return response('Payment lookup failed', 502);
        }

        if (($sync['approved_now'] ?? false) && ($sync['order'] ?? null) instanceof Order) {
            $order = $sync['order'];

            $this->adminUpdateService->record(
                'Pedido pagado',
                'Pedido #' . $order->id . ' fue aprobado por Mercado Pago',
                'pedido',
                '/admin/orders'
            );
        }

        return response('OK', 200);
    }

    private function syncMercadoPagoWebhookPayment(Request $request, string $paymentId): array
    {
        $orderToken = $request->query('order');

        if (is_string($orderToken) && $orderToken !== '') {
            $order = Order::where('admin_token', $orderToken)->first();

            if ($order) {
                return [
                    'order' => $order,
                    'approved_now' => $this->syncMercadoPagoPayment($order, $paymentId),
                ];
            }
        }

        $knownOrder = Order::where('payment_id', $paymentId)
            ->where('payment_method', Order::PAYMENT_METHOD_MERCADOPAGO)
            ->first();

        if ($knownOrder) {
            return [
                'order' => $knownOrder,
                'approved_now' => $this->syncMercadoPagoPayment($knownOrder, $paymentId),
            ];
        }

        foreach ($this->usableMercadoPagoAccounts() as $account) {
            try {
                $payment = $this->mercadoPagoCheckoutService->getPayment($account, $paymentId);
            } catch (RequestException) {
                continue;
            }

            $externalReference = (string) ($payment['external_reference'] ?? '');

            if ($externalReference === '') {
                continue;
            }

            $order = Order::where('admin_token', $externalReference)
                ->where('store_id', $account->store_id)
                ->where('payment_method', Order::PAYMENT_METHOD_MERCADOPAGO)
                ->first();

            if (! $order) {
                continue;
            }

            return [
                'order' => $order,
                'approved_now' => $this->applyMercadoPagoPayment($order, $payment),
            ];
        }

        return ['order' => null, 'approved_now' => false];
    }

    private function usableMercadoPagoAccounts()
    {
        return StorePaymentAccount::where('provider', StorePaymentAccount::PROVIDER_MERCADOPAGO)
            ->where('status', StorePaymentAccount::STATUS_CONNECTED)
            ->get()
            ->map(fn (StorePaymentAccount $account) => $this->mercadoPagoOAuthService->usableAccount($account))
            ->filter();
    }

    private function syncMercadoPagoPayment(Order $order, string $paymentId): bool
    {
        $order->loadMissing('store');
        $account = $this->mercadoPagoOAuthService->usableAccount($order->store?->mercadoPagoAccount()->first());

        if (! $account) {
            return false;
        }

        $wasApproved = $order->payment_status === Order::PAYMENT_STATUS_APPROVED;
        $payment = $this->mercadoPagoCheckoutService->getPayment($account, $paymentId);

        return $this->applyMercadoPagoPayment($order, $payment, $wasApproved);
    }

    private function applyMercadoPagoPayment(Order $order, array $payment, ?bool $wasApproved = null): bool
    {
        $wasApproved ??= $order->payment_status === Order::PAYMENT_STATUS_APPROVED;
        $approvedNow = $this->mercadoPagoCheckoutService->applyPaymentToOrder($order, $payment);

        if (! $wasApproved) {
            $this->checkoutService->releaseStockForUnpaidMercadoPagoOrder($order->refresh());
        }

        return $approvedNow;
    }

    private function checkoutContext(CheckoutRequest $request, bool $requiresWhatsApp = false): array|RedirectResponse
    {
        $validated = $request->validated();
        $store = $this->cartService->storeForRequest($request);
        $cart = $this->cartService->cartForStore($store);

        if (empty($cart)) {
            return $this->cartError($store, 'El carrito esta vacio.');
        }

        $store = $store ?: $this->cartService->resolveStore($cart);

        if (! $store) {
            return $this->cartError(null, 'No se pudo identificar la tienda del pedido.');
        }

        if (! $store->isAvailable()) {
            return $this->cartError($store, 'Esta tienda no esta disponible para recibir pedidos.');
        }

        if ($requiresWhatsApp && ! $store->whatsapp) {
            return $this->cartError($store, 'La tienda no tiene un WhatsApp configurado.');
        }

        if (! $this->cartService->matchesStore($cart, $store)) {
            return $this->cartError($store, 'El carrito contiene productos de otra tienda. Vacialo e intenta de nuevo.');
        }

        $validated = $this->reservationCheckoutData($request, $store, $validated);

        if ($validated instanceof RedirectResponse) {
            return $validated;
        }

        [$cartIsAvailable, $cartAvailabilityMessage] = $this->cartService->productsAreAvailable($cart, $store);

        if (! $cartIsAvailable) {
            return $this->cartError($store, $cartAvailabilityMessage);
        }

        return compact('validated', 'store', 'cart');
    }

    private function reservationCheckoutData(CheckoutRequest $request, Store $store, array $validated): array|RedirectResponse
    {
        if (! $store->isReservationStore()) {
            return $validated;
        }

        $validated = array_merge($validated, $request->validate(CheckoutRequest::reservationRules()));

        if (! $store->allowsReservationDateTime($validated['reservation_date'] ?? null, $validated['reservation_time'] ?? null)) {
            return $this->cartError($store, 'La fecha u hora seleccionada no esta dentro de la agenda disponible.', withInput: true);
        }

        return $validated;
    }

    private function cartError(?Store $store, ?string $message, bool $withInput = false): RedirectResponse
    {
        $redirect = redirect()->route('cart.index', $store ? ['store' => $store->slug] : []);
        $redirect = $redirect->with('error', $message ?: 'No se pudo procesar el carrito. Intenta de nuevo.');

        return $withInput ? $redirect->withInput() : $redirect;
    }

    private function hasValidMercadoPagoSignature(Request $request): bool
    {
        $secret = config('services.mercadopago.webhook_secret');

        if (! $secret) {
            return true;
        }

        $signature = (string) $request->header('x-signature');
        $requestId = (string) $request->header('x-request-id');

        if ($signature === '' || $requestId === '') {
            return false;
        }

        $parts = collect(explode(',', $signature))
            ->mapWithKeys(function (string $part) {
                [$key, $value] = array_pad(explode('=', trim($part), 2), 2, null);

                return $key && $value ? [$key => $value] : [];
            });

        $timestamp = $parts->get('ts');
        $receivedHash = $parts->get('v1');
        $dataId = $request->input('data.id') ?: $request->query('data_id') ?: $request->query('id');

        if (! $timestamp || ! $receivedHash || ! $dataId) {
            return false;
        }

        $manifest = "id:{$dataId};request-id:{$requestId};ts:{$timestamp};";
        $expectedHash = hash_hmac('sha256', $manifest, $secret);

        return hash_equals($expectedHash, $receivedHash);
    }
}
