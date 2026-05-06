<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class CartService
{
    private const LEGACY_CART_SESSION_KEY = 'cart';
    private const STORE_CARTS_SESSION_KEY = 'carts';

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
        $cart = $this->cartForStore($product->store);

        if (! empty($options['error'])) {
            return [$cart, $options['error']];
        }

        if (! $product->store?->isAvailable()) {
            return [$cart, 'Esta tienda no esta disponible para recibir pedidos.'];
        }

        $cartKey = $this->cartKey($product, $options);
        $currentQuantity = (int) ($cart[$cartKey]['quantity'] ?? 0);
        $requestedQuantity = $currentQuantity + $quantity;

        if (! $product->hasEnoughStock($requestedQuantity)) {
            return [$cart, $product->isSoldOut()
                ? 'Este producto esta agotado.'
                : 'Solo quedan ' . $product->stock_quantity . ' unidades disponibles.'];
        }

        if (isset($cart[$cartKey])) {
            $cart[$cartKey]['quantity'] = $requestedQuantity;
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

        $this->putCartForStore((int) $product->store_id, $cart);

        return [$cart, null];
    }

    public function cartForStore(?Store $store): array
    {
        if (! $store) {
            return $this->legacyCart();
        }

        $storeCart = session()->get($this->storeCartSessionKey((int) $store->id), []);

        if (! empty($storeCart)) {
            return $storeCart;
        }

        $legacyCart = $this->legacyCart();

        if (! empty($legacyCart) && $this->matchesStore($legacyCart, $store)) {
            $this->putCartForStore((int) $store->id, $legacyCart);
            $this->forgetLegacyCart();

            return $legacyCart;
        }

        return [];
    }

    public function putCartForStore(int $storeId, array $cart): void
    {
        session()->put($this->storeCartSessionKey($storeId), $cart);
    }

    public function forgetCartForStore(?Store $store): void
    {
        if ($store) {
            session()->forget($this->storeCartSessionKey((int) $store->id));

            return;
        }

        $this->forgetLegacyCart();
    }

    public function cartCountForStore(Store $store): int
    {
        return (int) collect($this->cartForStore($store))->sum('quantity');
    }

    public function firstCartWithItem(string $cartKey): array
    {
        foreach ($this->storeCarts() as $storeId => $cart) {
            if (isset($cart[$cartKey])) {
                return [(int) $storeId, $cart];
            }
        }

        $legacyCart = $this->legacyCart();

        if (isset($legacyCart[$cartKey])) {
            return [null, $legacyCart];
        }

        return [null, []];
    }

    public function updateItemQuantitySafely(string $cartKey, int $quantity): array
    {
        [$storeId, $cart] = $this->firstCartWithItem($cartKey);

        if (! isset($cart[$cartKey])) {
            return [null, 'Producto no encontrado en el carrito.'];
        }

        $productId = $cart[$cartKey]['product_id'] ?? $this->productIdFromCartKey($cartKey);
        $product = Product::with('store.user')->find($productId);

        if (! $product || ! $product->hasEnoughStock($quantity)) {
            return [$cart, $product?->isSoldOut()
                ? 'Este producto esta agotado.'
                : 'No hay suficiente stock disponible para esa cantidad.'];
        }

        $cart[$cartKey]['quantity'] = $quantity;
        $this->saveCart($storeId, $cart);

        return [$cart, null];
    }

    public function removeItem(string $cartKey): ?array
    {
        [$storeId, $cart] = $this->firstCartWithItem($cartKey);

        if (! isset($cart[$cartKey])) {
            return null;
        }

        unset($cart[$cartKey]);
        $this->saveCart($storeId, $cart);

        return $cart;
    }

    public function storeForRequest(Request $request): ?Store
    {
        return $this->storeFromSlug($request->query('store'))
            ?? $this->firstStoreWithCart();
    }

    public function storeFromSlug(?string $slug): ?Store
    {
        $slug = trim((string) $slug);

        if ($slug === '') {
            return null;
        }

        return Store::with('user')->where('slug', $slug)->first();
    }

    public function firstStoreWithCart(): ?Store
    {
        foreach ($this->storeCarts() as $storeId => $cart) {
            if (! empty($cart)) {
                return Store::with('user')->find($storeId);
            }
        }

        $legacyCart = $this->legacyCart();

        return $this->resolveStore($legacyCart);
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
            session()->put(self::LEGACY_CART_SESSION_KEY, $cart);
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

            if (! $product->hasEnoughStock((int) ($item['quantity'] ?? 1))) {
                return [false, $product->isSoldOut()
                    ? $product->name . ' esta agotado. Eliminalo del carrito e intenta de nuevo.'
                    : $product->name . ' no tiene stock suficiente para la cantidad seleccionada.'];
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
            'item_quantity' => $productId && isset($cart[$productId])
                ? $cart[$productId]['quantity']
                : null,
            'item_total' => $productId && isset($cart[$productId])
                ? $cart[$productId]['price'] * $cart[$productId]['quantity']
                : null,
        ];
    }

    private function storeCarts(): Collection
    {
        return collect(session()->get(self::STORE_CARTS_SESSION_KEY, []))
            ->filter(fn ($cart) => is_array($cart));
    }

    private function storeCartSessionKey(int $storeId): string
    {
        return self::STORE_CARTS_SESSION_KEY . '.' . $storeId;
    }

    private function saveCart(?int $storeId, array $cart): void
    {
        if (empty($cart)) {
            $storeId
                ? session()->forget($this->storeCartSessionKey($storeId))
                : $this->forgetLegacyCart();

            return;
        }

        if ($storeId) {
            $this->putCartForStore($storeId, $cart);

            return;
        }

        session()->put(self::LEGACY_CART_SESSION_KEY, $cart);
    }

    private function legacyCart(): array
    {
        return session()->get(self::LEGACY_CART_SESSION_KEY, []);
    }

    private function forgetLegacyCart(): void
    {
        session()->forget(self::LEGACY_CART_SESSION_KEY);
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
