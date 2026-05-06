<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Models\Product;
use App\Services\AdminUpdateService;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\WhatsAppOrderMessageBuilder;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        private CartService $cartService,
        private CheckoutService $checkoutService,
        private WhatsAppOrderMessageBuilder $whatsAppOrderMessageBuilder,
        private AdminUpdateService $adminUpdateService,
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

        return view('cart_checkout', compact('cart', 'store', 'total'));
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
        $validated = $request->validated();

        $store = $this->cartService->storeForRequest($request);
        $cart = $this->cartService->cartForStore($store);

        if (empty($cart)) {
            return redirect()->route('cart.index', ['store' => $store?->slug])->with('error', 'El carrito esta vacio.');
        }

        $store = $store ?: $this->cartService->resolveStore($cart);

        if (! $store) {
            return redirect()->route('cart.index')->with('error', 'No se pudo identificar la tienda del pedido.');
        }

        if (! $store->isAvailable()) {
            return redirect()->route('cart.index', ['store' => $store->slug])->with('error', 'Esta tienda no esta disponible para recibir pedidos.');
        }

        if (! $store->whatsapp) {
            return redirect()->route('cart.index', ['store' => $store->slug])->with('error', 'La tienda no tiene un WhatsApp configurado.');
        }

        if (! $this->cartService->matchesStore($cart, $store)) {
            return redirect()->route('cart.index', ['store' => $store->slug])->with('error', 'El carrito contiene productos de otra tienda. Vacialo e intenta de nuevo.');
        }

        if ($store->isReservationStore()) {
            $validated = array_merge($validated, $request->validate(CheckoutRequest::reservationRules()));

            if (! $store->allowsReservationDateTime($validated['reservation_date'] ?? null, $validated['reservation_time'] ?? null)) {
                return redirect()->route('cart.index', ['store' => $store->slug])
                    ->withInput()
                    ->with('error', 'La fecha u hora seleccionada no esta dentro de la agenda disponible.');
            }
        }

        [$cartIsAvailable, $cartAvailabilityMessage] = $this->cartService->productsAreAvailable($cart, $store);

        if (! $cartIsAvailable) {
            return redirect()->route('cart.index', ['store' => $store->slug])->with('error', $cartAvailabilityMessage);
        }

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
}
