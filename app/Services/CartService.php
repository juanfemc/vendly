<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;

class CartService
{
    public function requestedQuantity(Request $request): int
    {
        return max(1, min(99, (int) $request->input('quantity', 1)));
    }

    public function requestedOptions(Request $request, Product $product): array
    {
        $size = trim((string) $request->input('size'));
        $color = trim((string) $request->input('color'));

        if ($product->hasSizes() && ! in_array($size, $product->sizes ?? [], true)) {
            return ['error' => 'Selecciona una talla disponible.'];
        }

        if ($product->hasColors() && ! in_array($color, $product->colors ?? [], true)) {
            return ['error' => 'Selecciona un color disponible.'];
        }

        return [
            'size' => $product->hasSizes() && $size !== '' ? $size : null,
            'color' => $product->hasColors() && $color !== '' ? $color : null,
        ];
    }

    public function addProduct(Product $product, int $quantity = 1, array $options = []): array
    {
        $cart = session()->get('cart', []);

        if (! empty($options['error'])) {
            return [$cart, $options['error']];
        }

        if (! $product->store?->isAvailable()) {
            return [$cart, 'Esta tienda no esta disponible para recibir pedidos.'];
        }

        if (! $this->canAcceptStore($cart, (int) $product->store_id)) {
            return [$cart, 'Solo puedes agregar productos de una tienda a la vez.'];
        }

        $cartKey = $this->cartKey($product, $options);

        if (isset($cart[$cartKey])) {
            $cart[$cartKey]['quantity'] += $quantity;
        } else {
            $cart[$cartKey] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'quantity' => $quantity,
                'store_id' => $product->store_id,
                'image' => $product->image,
                'size' => $options['size'] ?? null,
                'color' => $options['color'] ?? null,
            ];
        }

        session()->put('cart', $cart);

        return [$cart, null];
    }

    public function resolveStore(array &$cart): ?Store
    {
        if (empty($cart)) {
            return null;
        }

        $firstKey = array_key_first($cart);
        $firstItem = $cart[$firstKey] ?? null;

        if (isset($firstItem['store_id'])) {
            return Store::with('user')->find($firstItem['store_id']);
        }

        $productId = $firstItem['product_id'] ?? $this->productIdFromCartKey((string) $firstKey);
        $product = Product::find($productId);

        if (! $product?->store_id) {
            return null;
        }

        if (isset($cart[$firstKey]) && is_array($cart[$firstKey])) {
            $cart[$firstKey]['store_id'] = $product->store_id;
            session()->put('cart', $cart);
        }

        return Store::with('user')->find($product->store_id);
    }

    public function matchesStore(array &$cart, Store $store): bool
    {
        if (empty($cart)) {
            return false;
        }

        foreach ($cart as $productId => $item) {
            $itemStoreId = $item['store_id'] ?? null;

            if (! $itemStoreId) {
                $product = Product::find($item['product_id'] ?? $this->productIdFromCartKey((string) $productId));
                $itemStoreId = $product?->store_id;

                if (isset($cart[$productId]) && is_array($cart[$productId]) && $itemStoreId) {
                    $cart[$productId]['store_id'] = $itemStoreId;
                }
            }

            if ((int) $itemStoreId !== (int) $store->id) {
                return false;
            }
        }

        session()->put('cart', $cart);

        return true;
    }

    public function productsAreAvailable(array $cart, Store $store): array
    {
        foreach ($cart as $cartKey => $item) {
            $productId = $item['product_id'] ?? $this->productIdFromCartKey((string) $cartKey);
            $product = Product::with('store.user')->find($productId);

            if (! $product || (int) $product->store_id !== (int) $store->id || ! $product->store?->isAvailable()) {
                return [false, 'Uno de los productos del carrito ya no esta disponible. Eliminalo e intenta de nuevo.'];
            }

            if (! empty($item['size']) && ! in_array($item['size'], $product->sizes ?? [], true)) {
                return [false, 'Una talla del carrito ya no esta disponible. Actualiza tu carrito e intenta de nuevo.'];
            }

            if (! empty($item['color']) && ! in_array($item['color'], $product->colors ?? [], true)) {
                return [false, 'Un color del carrito ya no esta disponible. Actualiza tu carrito e intenta de nuevo.'];
            }
        }

        return [true, null];
    }

    public function total(array $cart): int
    {
        return (int) collect($cart)->sum(fn ($item) => $item['price'] * $item['quantity']);
    }

    public function responsePayload(array $cart, ?string $productId = null, string $message = 'Carrito actualizado'): array
    {
        return [
            'message' => $message,
            'cart_count' => collect($cart)->sum('quantity'),
            'cart_is_empty' => empty($cart),
            'total' => $this->total($cart),
            'item_total' => $productId && isset($cart[$productId])
                ? $cart[$productId]['price'] * $cart[$productId]['quantity']
                : null,
        ];
    }

    private function canAcceptStore(array $cart, int $storeId): bool
    {
        foreach ($cart as $productId => $item) {
            $itemStoreId = $item['store_id'] ?? Product::find($item['product_id'] ?? $this->productIdFromCartKey((string) $productId))?->store_id;

            if ((int) $itemStoreId !== $storeId) {
                return false;
            }
        }

        return true;
    }

    private function cartKey(Product $product, array $options): string
    {
        $variant = trim(($options['size'] ?? '') . '|' . ($options['color'] ?? ''), '|');

        if ($variant === '') {
            return (string) $product->id;
        }

        return $product->id . ':' . substr(sha1($variant), 0, 12);
    }

    private function productIdFromCartKey(string $key): int
    {
        return (int) explode(':', $key)[0];
    }
}
