<?php

namespace App\Services;

use App\Models\Order;
use App\Models\StorePaymentAccount;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class MercadoPagoCheckoutService
{
    private const PREFERENCES_URL = 'https://api.mercadopago.com/checkout/preferences';
    private const PAYMENTS_URL = 'https://api.mercadopago.com/v1/payments';
    private const MERCHANT_ORDERS_SEARCH_URL = 'https://api.mercadopago.com/merchant_orders/search';

    public function createPreference(StorePaymentAccount $account, Order $order): array
    {
        $response = Http::withToken($account->access_token)
            ->acceptJson()
            ->asJson()
            ->post(self::PREFERENCES_URL, $this->payload($order->loadMissing(['items', 'store'])))
            ->throw()
            ->json();

        return [
            'id' => $response['id'] ?? null,
            'redirect_url' => $response['init_point'] ?? $response['sandbox_init_point'] ?? null,
            'raw' => $response,
        ];
    }

    public function getPayment(StorePaymentAccount $account, string $paymentId): array
    {
        return Http::withToken($account->access_token)
            ->acceptJson()
            ->get(self::PAYMENTS_URL . '/' . $paymentId)
            ->throw()
            ->json();
    }

    public function getMerchantOrderByPreference(StorePaymentAccount $account, string $preferenceId): ?array
    {
        $response = Http::withToken($account->access_token)
            ->acceptJson()
            ->get(self::MERCHANT_ORDERS_SEARCH_URL, [
                'preference_id' => $preferenceId,
            ])
            ->throw()
            ->json();

        return $response['elements'][0] ?? null;
    }

    public function applyPaymentToOrder(Order $order, array $payment): bool
    {
        if (! $this->paymentBelongsToOrder($order, $payment)) {
            return false;
        }

        $status = (string) ($payment['status'] ?? '');
        $previousPaymentStatus = $order->payment_status;

        $updates = [
            'payment_provider_reference' => (string) ($payment['id'] ?? $order->payment_provider_reference),
            'payment_id' => (string) ($payment['id'] ?? $order->payment_id),
            'payment_status' => $this->orderPaymentStatus($status),
        ];

        if ($status === 'approved') {
            $updates['status'] = 'pagado';
            $updates['paid_at'] = $this->approvedAt($payment);
        }

        if (in_array($status, ['cancelled', 'refunded', 'charged_back'], true)) {
            $updates['paid_at'] = null;

            if ($order->status === 'pagado') {
                $updates['status'] = 'pendiente';
            }
        }

        if ($status === 'rejected') {
            $updates['paid_at'] = null;

            if ($order->status === 'pagado') {
                $updates['status'] = 'pendiente';
            }
        }

        $order->update($updates);

        return $previousPaymentStatus !== Order::PAYMENT_STATUS_APPROVED
            && $order->payment_status === Order::PAYMENT_STATUS_APPROVED;
    }

    public function approvedPaymentFromMerchantOrder(Order $order, array $merchantOrder): ?array
    {
        if (! $this->merchantOrderBelongsToOrder($order, $merchantOrder)) {
            return null;
        }

        foreach (($merchantOrder['payments'] ?? []) as $payment) {
            if (($payment['status'] ?? null) !== 'approved') {
                continue;
            }

            $payment['external_reference'] = $merchantOrder['external_reference'] ?? null;

            if ($this->paymentBelongsToOrder($order, $payment)) {
                return $payment;
            }
        }

        return null;
    }

    public function merchantOrderHasActivePayment(array $merchantOrder): bool
    {
        $activeStatuses = ['pending', 'in_process', 'in_mediation', 'authorized'];

        foreach (($merchantOrder['payments'] ?? []) as $payment) {
            if (in_array((string) ($payment['status'] ?? ''), $activeStatuses, true)) {
                return true;
            }
        }

        return false;
    }

    private function payload(Order $order): array
    {
        $payload = [
            'items' => $order->items->map(fn ($item) => [
                'id' => (string) ($item->product_id ?: $item->id),
                'title' => $item->displayName(),
                'quantity' => (int) $item->quantity,
                'unit_price' => (float) $item->price,
                'currency_id' => 'COP',
            ])->when((float) ($order->shipping_cost ?? 0) > 0, fn ($items) => $items->push([
                'id' => 'shipping',
                'title' => 'Envio: ' . ($order->shipping_method ?: 'Envio'),
                'quantity' => 1,
                'unit_price' => (float) $order->shipping_cost,
                'currency_id' => 'COP',
            ]))->values()->all(),
            'payer' => $this->payer($order),
            'back_urls' => [
                'success' => route('cart.mercadopago.return', ['order' => $order, 'result' => 'success']),
                'failure' => route('cart.mercadopago.return', ['order' => $order, 'result' => 'failure']),
                'pending' => route('cart.mercadopago.return', ['order' => $order, 'result' => 'pending']),
            ],
            'notification_url' => route('cart.mercadopago.webhook'),
            'auto_return' => 'approved',
            'external_reference' => $order->admin_token,
            'metadata' => [
                'order_id' => $order->id,
                'store_id' => $order->store_id,
            ],
        ];

        if ($order->payment_expires_at) {
            $payload['expires'] = true;
            $payload['date_of_expiration'] = $order->payment_expires_at->toIso8601String();
        }

        return $payload;
    }

    private function paymentBelongsToOrder(Order $order, array $payment): bool
    {
        $externalReference = (string) ($payment['external_reference'] ?? '');
        $amount = (float) ($payment['transaction_amount'] ?? $payment['total_paid_amount'] ?? 0);

        return hash_equals($order->admin_token, $externalReference)
            && $amount >= (float) $order->total;
    }

    private function merchantOrderBelongsToOrder(Order $order, array $merchantOrder): bool
    {
        $externalReference = (string) ($merchantOrder['external_reference'] ?? '');
        $preferenceId = (string) ($merchantOrder['preference_id'] ?? '');

        return hash_equals($order->admin_token, $externalReference)
            && $order->payment_preference_id
            && hash_equals((string) $order->payment_preference_id, $preferenceId);
    }

    private function orderPaymentStatus(string $mercadoPagoStatus): string
    {
        return match ($mercadoPagoStatus) {
            'approved' => Order::PAYMENT_STATUS_APPROVED,
            'rejected' => Order::PAYMENT_STATUS_REJECTED,
            'cancelled' => Order::PAYMENT_STATUS_CANCELLED,
            'refunded', 'charged_back' => Order::PAYMENT_STATUS_REFUNDED,
            default => Order::PAYMENT_STATUS_PENDING,
        };
    }

    private function approvedAt(array $payment): Carbon
    {
        $approvedAt = $payment['date_approved'] ?? null;

        if (is_string($approvedAt) && $approvedAt !== '') {
            return Carbon::parse($approvedAt);
        }

        return now();
    }

    private function payer(Order $order): array
    {
        $nameParts = preg_split('/\s+/', trim((string) $order->customer_name), 2) ?: [];
        $payer = [
            'name' => $nameParts[0] ?? null,
            'surname' => $nameParts[1] ?? null,
            'phone' => [
                'number' => preg_replace('/\D+/', '', (string) $order->customer_phone),
            ],
            'identification' => [
                'type' => 'CC',
                'number' => preg_replace('/\D+/', '', (string) $order->customer_document),
            ],
            'address' => [
                'street_name' => $order->customer_address,
            ],
        ];

        return array_filter($payer, fn ($value) => $value !== null && $value !== '');
    }
}
