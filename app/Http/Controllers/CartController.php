<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Models\Product;
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

        return redirect('/cart')->with('success', 'Producto agregado');
    }

    public function index()
    {
        $cart = session()->get('cart', []);
        $store = $this->cartService->resolveStore($cart);
        $total = $this->cartService->total($cart);

        return view('cart_checkout', compact('cart', 'store', 'total'));
    }

    public function updateItem(Request $request, $id)
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        $cart = session()->get('cart', []);

        if (! isset($cart[$id])) {
            return response()->json(['message' => 'Producto no encontrado en el carrito.'], 404);
        }

        $cart[$id]['quantity'] = (int) $validated['quantity'];
        session()->put('cart', $cart);

        return response()->json($this->cartService->responsePayload($cart, (string) $id, 'Cantidad actualizada'));
    }

    public function removeItem($id)
    {
        $cart = session()->get('cart', []);

        if (! isset($cart[$id])) {
            return response()->json(['message' => 'Producto no encontrado en el carrito.'], 404);
        }

        unset($cart[$id]);
        session()->put('cart', $cart);

        return response()->json($this->cartService->responsePayload($cart, null, 'Producto eliminado del carrito'));
    }

    public function clear()
    {
        session()->forget('cart');

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

        $cart = session()->get('cart', []);

        if (empty($cart)) {
            return redirect('/cart')->with('error', 'El carrito esta vacio.');
        }

        $store = $this->cartService->resolveStore($cart);

        if (! $store) {
            return redirect('/cart')->with('error', 'No se pudo identificar la tienda del pedido.');
        }

        if (! $store->isAvailable()) {
            return redirect('/cart')->with('error', 'Esta tienda no esta disponible para recibir pedidos.');
        }

        if (! $store->whatsapp) {
            return redirect('/cart')->with('error', 'La tienda no tiene un WhatsApp configurado.');
        }

        if (! $this->cartService->matchesStore($cart, $store)) {
            return redirect('/cart')->with('error', 'El carrito contiene productos de otra tienda. Vacialo e intenta de nuevo.');
        }

        [$cartIsAvailable, $cartAvailabilityMessage] = $this->cartService->productsAreAvailable($cart, $store);

        if (! $cartIsAvailable) {
            return redirect('/cart')->with('error', $cartAvailabilityMessage);
        }

        $order = $this->checkoutService->createOrder($store, $cart, $validated);
        $order->load(['items.product', 'store']);

        $url = $this->whatsAppOrderMessageBuilder->url($order);

        if (! $url) {
            return redirect('/cart')->with('error', 'La tienda no tiene un WhatsApp configurado.');
        }

        session()->forget('cart');

        return redirect($url);
    }
}
