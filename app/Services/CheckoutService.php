<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(private CartService $cartService)
    {
    }

    public function createOrder(Store $store, array $cart, array $customerData): Order
    {
        $fullName = trim($customerData['name'] . ' ' . $customerData['last_name']);
        $fullAddress = $customerData['address'];

        if (! empty($customerData['apartment'])) {
            $fullAddress .= ', ' . $customerData['apartment'];
        }

        $notes = $this->notes($customerData);
        $total = $this->cartService->total($cart);

        return DB::transaction(function () use ($fullName, $customerData, $fullAddress, $notes, $total, $store, $cart) {
            $order = Order::create([
                'customer_name' => $fullName,
                'customer_phone' => $customerData['phone'],
                'customer_address' => $fullAddress,
                'customer_city' => $customerData['city'],
                'customer_document' => $customerData['document'],
                'reservation_date' => $customerData['reservation_date'] ?? null,
                'reservation_time' => $customerData['reservation_time'] ?? null,
                'notes' => $notes,
                'status' => 'pendiente',
                'total' => $total,
                'store_id' => $store->id,
            ]);

            foreach ($cart as $cartKey => $item) {
                $product = $this->reserveStock($store, $cartKey, $item);

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product?->id ?? ($item['product_id'] ?? (int) explode(':', (string) $cartKey)[0]),
                    'product_name' => $item['name'] ?? 'Producto',
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'size' => $item['size'] ?? null,
                    'color' => $item['color'] ?? null,
                ]);
            }

            return $order;
        });
    }

    private function reserveStock(Store $store, string|int $cartKey, array $item): ?Product
    {
        if ($store->isReservationStore()) {
            return null;
        }

        $productId = $item['product_id'] ?? (int) explode(':', (string) $cartKey)[0];
        $product = Product::whereKey($productId)->lockForUpdate()->first();
        $quantity = (int) ($item['quantity'] ?? 1);

        if (! Product::supportsInventoryColumns()) {
            return $product;
        }

        if (! $product || (int) $product->store_id !== (int) $store->id || ! $product->hasEnoughStock($quantity)) {
            throw ValidationException::withMessages([
                'cart' => 'Uno de los productos ya no tiene stock suficiente. Actualiza el carrito e intenta de nuevo.',
            ]);
        }

        if ($product->stock_quantity !== null) {
            $newStock = max(0, $product->stock_quantity - $quantity);

            $product->forceFill([
                'stock_quantity' => $newStock,
                'is_sold_out' => $newStock === 0,
            ])->save();
        }

        return $product;
    }

    private function notes(array $customerData): ?string
    {
        $notes = [];

        if (! empty($customerData['email'])) {
            $notes[] = 'Email: ' . $customerData['email'];
        }

        if (! empty($customerData['region'])) {
            $notes[] = 'Provincia/Estado: ' . $customerData['region'];
        }

        if (! empty($customerData['notes'])) {
            $notes[] = 'Notas: ' . $customerData['notes'];
        }

        return ! empty($notes) ? implode("\n", $notes) : null;
    }
}
