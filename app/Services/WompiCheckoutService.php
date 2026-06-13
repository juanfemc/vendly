<?php

namespace App\Services;

use App\Models\Order;
use App\Models\StorePaymentAccount;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WompiCheckoutService
{
    private const CHECKOUT_URL = 'https://checkout.wompi.co/p/';
    private const SANDBOX_API_URL = 'https://sandbox.wompi.co/v1';
    private const PRODUCTION_API_URL = 'https://production.wompi.co/v1';
    private const CURRENCY = 'COP';

    public function checkoutUrl(StorePaymentAccount $account, Order $order): string
    {
        $amountInCents = $this->amountInCents($order);
        $expirationTime = $this->expirationTime($order);
        $payload = [
            'public-key' => $account->public_key,
            'currency' => self::CURRENCY,
            'amount-in-cents' => $amountInCents,
            'reference' => $order->admin_token,
            'redirect-url' => route('cart.wompi.return', ['order' => $order, 'result' => 'pending']),
            'signature:integrity' => $this->integritySignature($account, $order->admin_token, $amountInCents, $expirationTime),
        ];

        if ($expirationTime) {
            $payload['expiration-time'] = $expirationTime;
        }

        return self::CHECKOUT_URL . '?' . Arr::query($payload);
    }

    public function getTransaction(StorePaymentAccount $account, string $transactionId): array
    {
        return Http::withToken($account->private_key)
            ->acceptJson()
            ->get($this->apiUrl($account) . '/transactions/' . $transactionId)
            ->throw()
            ->json('data', []);
    }

    public function applyTransactionToOrder(Order $order, array $transaction): bool
    {
        if (! $this->transactionBelongsToOrder($order, $transaction)) {
            return false;
        }

        $status = strtoupper((string) ($transaction['status'] ?? ''));
        $previousPaymentStatus = $order->payment_status;

        $updates = [
            'payment_provider_reference' => (string) ($transaction['id'] ?? $order->payment_provider_reference),
            'payment_id' => (string) ($transaction['id'] ?? $order->payment_id),
            'payment_status' => $this->orderPaymentStatus($status),
        ];

        if ($status === 'APPROVED') {
            $approvedAt = $this->approvedAt($transaction);

            if (! $this->orderCanBeApproved($order, $approvedAt)) {
                $order->update([
                    'payment_provider_reference' => $updates['payment_provider_reference'],
                    'payment_id' => $updates['payment_id'],
                ]);

                Log::warning('Wompi approval ignored because the order can no longer be approved.', [
                    'order_id' => $order->id,
                    'payment_status' => $order->payment_status,
                    'payment_expires_at' => $order->payment_expires_at?->toIso8601String(),
                    'transaction_id' => $transaction['id'] ?? null,
                    'transaction_approved_at' => $approvedAt->toIso8601String(),
                ]);

                return false;
            }

            $updates['status'] = 'pagado';
            $updates['paid_at'] = $approvedAt;
        }

        if (in_array($status, ['DECLINED', 'VOIDED', 'ERROR'], true)) {
            $updates['paid_at'] = null;

            if ($order->status === 'pagado') {
                $updates['status'] = 'pendiente';
            }
        }

        $order->update($updates);

        return $previousPaymentStatus !== Order::PAYMENT_STATUS_APPROVED
            && $order->payment_status === Order::PAYMENT_STATUS_APPROVED;
    }

    public function hasValidEventSignature(StorePaymentAccount $account, Request $request): bool
    {
        if (! $account->events_secret) {
            return false;
        }

        $signature = $request->input('signature');

        if (! is_array($signature)) {
            return false;
        }

        $properties = $signature['properties'] ?? [];
        $receivedChecksum = (string) ($signature['checksum'] ?? '');
        $timestamp = (string) ($signature['timestamp'] ?? $request->input('timestamp', ''));

        if (! is_array($properties) || $receivedChecksum === '') {
            return false;
        }

        $payload = collect($properties)
            ->map(fn (string $property) => $this->eventSignatureValue($request, $property))
            ->implode('');

        $expectedChecksum = hash('sha256', $payload . $timestamp . $account->events_secret);

        return hash_equals(strtoupper($expectedChecksum), strtoupper($receivedChecksum));
    }

    private function integritySignature(StorePaymentAccount $account, string $reference, int $amountInCents, ?string $expirationTime = null): string
    {
        $payload = $reference . $amountInCents . self::CURRENCY;

        if ($expirationTime) {
            $payload .= $expirationTime;
        }

        return hash('sha256', $payload . $account->integrity_secret);
    }

    private function expirationTime(Order $order): ?string
    {
        return $order->payment_expires_at
            ? $order->payment_expires_at->copy()->utc()->format('Y-m-d\TH:i:s.v\Z')
            : null;
    }

    private function eventSignatureValue(Request $request, string $property): mixed
    {
        $data = $request->input('data', []);

        return data_get($data, $property)
            ?? data_get($request->all(), $property)
            ?? data_get($data, 'transaction.' . $property)
            ?? '';
    }

    private function transactionBelongsToOrder(Order $order, array $transaction): bool
    {
        $reference = (string) ($transaction['reference'] ?? '');
        $amountInCents = (int) ($transaction['amount_in_cents'] ?? 0);

        return hash_equals($order->admin_token, $reference)
            && $amountInCents >= $this->amountInCents($order);
    }

    private function orderCanBeApproved(Order $order, Carbon $approvedAt): bool
    {
        if ($order->payment_status !== Order::PAYMENT_STATUS_PENDING) {
            return false;
        }

        if ($order->payment_expires_at && $approvedAt->gt($order->payment_expires_at)) {
            return false;
        }

        return true;
    }

    private function amountInCents(Order $order): int
    {
        return (int) round(((float) $order->total) * 100);
    }

    private function orderPaymentStatus(string $wompiStatus): string
    {
        return match ($wompiStatus) {
            'APPROVED' => Order::PAYMENT_STATUS_APPROVED,
            'DECLINED', 'ERROR' => Order::PAYMENT_STATUS_REJECTED,
            'VOIDED' => Order::PAYMENT_STATUS_CANCELLED,
            default => Order::PAYMENT_STATUS_PENDING,
        };
    }

    private function approvedAt(array $transaction): Carbon
    {
        $finalizedAt = $transaction['finalized_at'] ?? $transaction['created_at'] ?? null;

        if (is_string($finalizedAt) && $finalizedAt !== '') {
            return Carbon::parse($finalizedAt);
        }

        return now();
    }

    private function apiUrl(StorePaymentAccount $account): string
    {
        return $account->mode === StorePaymentAccount::MODE_PRODUCTION
            ? self::PRODUCTION_API_URL
            : self::SANDBOX_API_URL;
    }
}
