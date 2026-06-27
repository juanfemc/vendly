<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(
        private CartService $cartService,
        private MercadoPagoCheckoutService $mercadoPagoCheckoutService,
        private MercadoPagoOAuthService $mercadoPagoOAuthService,
    ) {
    }

    public function createOrder(Store $store, array $cart, array $customerData, array $paymentData = []): Order
    {
        $fullName = trim($customerData['name'] . ' ' . $customerData['last_name']);
        $fullAddress = $customerData['address'];

        if (! empty($customerData['apartment'])) {
            $fullAddress .= ', ' . $customerData['apartment'];
        }

        $notes = $this->notes($customerData);
        $subtotal = $this->cartService->total($cart);
        $shipping = $this->shipping($store, $customerData, $subtotal);
        $total = $subtotal + $shipping['cost'];

        return DB::transaction(function () use ($fullName, $customerData, $fullAddress, $notes, $total, $shipping, $store, $cart, $paymentData) {
            $orderData = array_merge([
                'customer_name' => $fullName,
                'customer_phone' => $customerData['phone'],
                'customer_address' => $fullAddress,
                'customer_neighborhood' => $customerData['neighborhood'] ?? null,
                'customer_city' => $customerData['city'],
                'customer_document' => $customerData['document'],
                'reservation_date' => $customerData['reservation_date'] ?? null,
                'reservation_time' => $customerData['reservation_time'] ?? null,
                'notes' => $notes,
                'status' => 'pendiente',
                'total' => $total,
                'store_id' => $store->id,
            ], $paymentData);

            if (Order::supportsShippingColumns()) {
                $orderData['shipping_method'] = $shipping['name'];
                $orderData['shipping_cost'] = $shipping['cost'];
            }

            if (Order::supportsTermsAcceptanceColumns() && $store->requiresTermsAcceptance()) {
                if (! $this->acceptedTerms($customerData)) {
                    throw ValidationException::withMessages([
                        'terms_acceptance' => 'Debes aceptar los terminos y condiciones de la tienda para continuar.',
                    ]);
                }

                $orderData = array_merge($orderData, $this->termsAcceptanceData($store));
            }

            $order = Order::create($orderData);

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

    public function deleteOrderAndReleaseStock(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order->loadMissing(['items.product', 'store']);

            $this->releaseStock($order);

            $order->delete();
        });
    }

    public function expirePendingOnlinePaymentOrders(): int
    {
        if (! Order::supportsPaymentExpirationColumn()) {
            return 0;
        }

        $expiredCount = 0;
        $graceMinutes = max(0, (int) config('services.payments.expiration_grace_minutes', 30));
        $expiresBefore = now()->subMinutes($graceMinutes);

        $expiredOrders = Order::with(['items.product', 'store'])
            ->whereIn('payment_method', [Order::PAYMENT_METHOD_MERCADOPAGO, Order::PAYMENT_METHOD_WOMPI])
            ->where('payment_status', Order::PAYMENT_STATUS_PENDING)
            ->whereNotNull('payment_expires_at')
            ->where('payment_expires_at', '<=', $expiresBefore)
            ->get();

        foreach ($expiredOrders as $order) {
            if ($order->payment_method === Order::PAYMENT_METHOD_MERCADOPAGO && ! $this->mercadoPagoOrderCanExpire($order)) {
                continue;
            }

            $expired = DB::transaction(function () use ($order, $expiresBefore) {
                $order = Order::whereKey($order->getKey())->lockForUpdate()->first();

                if (! $order) {
                    return false;
                }

                $order->loadMissing(['items.product', 'store']);

                if ($order->payment_status !== Order::PAYMENT_STATUS_PENDING
                    || ! $order->payment_expires_at
                    || $order->payment_expires_at->gt($expiresBefore)
                ) {
                    return false;
                }

                $this->releaseStock($order);

                $order->update([
                    'status' => 'pendiente',
                    'payment_status' => Order::PAYMENT_STATUS_EXPIRED,
                    'paid_at' => null,
                ]);

                return true;
            });

            if ($expired) {
                $expiredCount++;
            }
        }

        return $expiredCount;
    }

    public function expirePendingMercadoPagoOrders(): int
    {
        return $this->expirePendingOnlinePaymentOrders();
    }

    public function releaseStockForUnpaidOnlinePaymentOrder(Order $order): bool
    {
        if (! in_array($order->payment_method, [Order::PAYMENT_METHOD_MERCADOPAGO, Order::PAYMENT_METHOD_WOMPI], true)) {
            return false;
        }

        if (! in_array($order->payment_status, [
            Order::PAYMENT_STATUS_REJECTED,
            Order::PAYMENT_STATUS_CANCELLED,
            Order::PAYMENT_STATUS_EXPIRED,
        ], true)) {
            return false;
        }

        return DB::transaction(function () use ($order) {
            $order = Order::whereKey($order->getKey())->lockForUpdate()->first();

            if (! $order) {
                return false;
            }

            $order->loadMissing(['items.product', 'store']);

            if (! in_array($order->payment_method, [Order::PAYMENT_METHOD_MERCADOPAGO, Order::PAYMENT_METHOD_WOMPI], true)
                || ! in_array($order->payment_status, [
                    Order::PAYMENT_STATUS_REJECTED,
                    Order::PAYMENT_STATUS_CANCELLED,
                    Order::PAYMENT_STATUS_EXPIRED,
                ], true)
                || $order->status !== 'pendiente'
                || (Order::supportsPaymentExpirationColumn() && $order->payment_expires_at === null)
            ) {
                return false;
            }

            $this->releaseStock($order);

            if (Order::supportsPaymentExpirationColumn()) {
                $order->update([
                    'payment_expires_at' => null,
                ]);
            }

            return true;
        });
    }

    private function mercadoPagoOrderCanExpire(Order $order): bool
    {
        if (! $order->payment_preference_id) {
            return true;
        }

        $account = $this->mercadoPagoOAuthService->usableAccount($order->store?->mercadoPagoAccount()->first());

        if (! $account) {
            return false;
        }

        try {
            $merchantOrder = $this->mercadoPagoCheckoutService->getMerchantOrderByPreference($account, $order->payment_preference_id);
        } catch (ConnectionException|RequestException) {
            return false;
        }

        if (! $merchantOrder) {
            return true;
        }

        $approvedPayment = $this->mercadoPagoCheckoutService->approvedPaymentFromMerchantOrder($order, $merchantOrder);

        if ($approvedPayment) {
            $this->mercadoPagoCheckoutService->applyPaymentToOrder($order, $approvedPayment);

            return false;
        }

        if ($this->mercadoPagoCheckoutService->merchantOrderHasActivePayment($merchantOrder)) {
            return false;
        }

        return true;
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

    private function releaseStock(Order $order): void
    {
        if ($order->store?->isReservationStore() || ! Product::supportsInventoryColumns()) {
            return;
        }

        foreach ($order->items as $item) {
            $productId = $item->product_id ?: $item->product?->id;

            if (! $productId) {
                continue;
            }

            $product = Product::whereKey($productId)->lockForUpdate()->first();

            if (! $product || $product->stock_quantity === null) {
                continue;
            }

            $product->forceFill([
                'stock_quantity' => $product->stock_quantity + (int) $item->quantity,
                'is_sold_out' => false,
            ])->save();
        }
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

    private function shipping(Store $store, array $customerData, float $subtotal): array
    {
        if ($store->isReservationStore()) {
            return ['name' => null, 'cost' => 0];
        }

        if ($store->localDeliveryEnabled()) {
            return $store->deliveryByCity(
                $customerData['city'] ?? null,
                $subtotal,
                $customerData['city_code'] ?? null,
            )
                ?? ['name' => null, 'cost' => 0];
        }

        $methods = $store->shippingMethods();

        if ($methods === []) {
            return ['name' => null, 'cost' => 0];
        }

        $method = $store->shippingMethodByKey($customerData['shipping_method'] ?? null);

        if (! $method) {
            throw ValidationException::withMessages([
                'shipping_method' => 'Selecciona un metodo de envio.',
            ]);
        }

        return [
            'name' => $method['name'],
            'cost' => $store->shippingCostForSubtotal($method, $subtotal),
        ];
    }

    private function termsAcceptanceData(Store $store): array
    {
        $key = (string) config('app.key', 'vendly');
        $ip = (string) request()->ip();
        $userAgent = (string) request()->userAgent();
        $snapshot = trim((string) $store->terms_content);

        if ($snapshot === '' && filled($store->terms_url)) {
            $snapshot = 'Terminos publicados en: ' . $store->terms_url;
        }

        return [
            'terms_accepted_at' => now(),
            'terms_version' => $store->termsAcceptanceVersion(),
            'terms_snapshot' => $snapshot ?: $store->termsAcceptanceTitle(),
            'terms_url' => trim((string) $store->terms_url) ?: null,
            'terms_ip_hash' => $ip !== '' ? hash_hmac('sha256', $ip, $key) : null,
            'terms_user_agent_hash' => $userAgent !== '' ? hash_hmac('sha256', $userAgent, $key) : null,
        ];
    }

    private function acceptedTerms(array $customerData): bool
    {
        return in_array($customerData['terms_acceptance'] ?? null, [true, 1, '1', 'on', 'yes'], true);
    }
}
