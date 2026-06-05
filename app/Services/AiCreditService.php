<?php

namespace App\Services;

use App\Models\AiCreditTransaction;
use App\Models\AiGeneration;
use App\Models\Store;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AiCreditService
{
    public const MONTHLY_PREMIUM_CREDITS = 250;
    private const BUCKET_MONTHLY = 'monthly';
    private const BUCKET_PURCHASED = 'purchased';

    public const COSTS = [
        AiContentService::PRODUCT_NAME => 1,
        AiContentService::PRODUCT_BADGES => 1,
        AiContentService::PRODUCT_FEATURES => 2,
        AiContentService::ANNOUNCEMENT => 2,
        AiContentService::PRODUCT_DESCRIPTION => 3,
        AiContentService::PRODUCT_IMAGE => 15,
        AiContentService::STORE_COVER_IMAGE => 25,
    ];

    public const PACKAGES = [
        'ai_100' => [
            'credits' => 100,
            'price_cop' => 9900,
            'label' => '100 creditos IA',
        ],
        'ai_300' => [
            'credits' => 300,
            'price_cop' => 24900,
            'label' => '300 creditos IA',
        ],
        'ai_1000' => [
            'credits' => 1000,
            'price_cop' => 69900,
            'label' => '1000 creditos IA',
        ],
    ];

    public function cost(string $type): int
    {
        return self::COSTS[$type] ?? 0;
    }

    public function balance(Store $store): int
    {
        $this->grantMonthlyPremiumCredits($store);

        return $this->availableBalance($store);
    }

    public function consume(Store $store, string $type, AiGeneration $generation, ?int $userId = null): AiCreditTransaction
    {
        $cost = $this->cost($type);

        if ($cost < 1) {
            throw new RuntimeException('Tipo de consumo IA no valido.');
        }

        return DB::transaction(function () use ($store, $type, $generation, $userId, $cost) {
            Store::whereKey($store->id)->lockForUpdate()->first();
            $this->grantMonthlyPremiumCredits($store);
            $monthlyBalance = $this->monthlyBalance($store);
            $purchasedBalance = $this->purchasedBalance($store);
            $balance = $monthlyBalance + $purchasedBalance;

            if ($balance < $cost) {
                throw new RuntimeException('No tienes creditos IA suficientes. Compra un paquete extra o espera la renovacion mensual.');
            }

            $remainingCost = $cost;
            $transaction = null;

            if ($monthlyBalance > 0) {
                $monthlyCharge = min($monthlyBalance, $remainingCost);
                $transaction = $this->usageTransaction($store, $type, $generation, $userId, $monthlyCharge, $this->monthlyBucket());
                $remainingCost -= $monthlyCharge;
            }

            if ($remainingCost > 0) {
                $transaction = $this->usageTransaction($store, $type, $generation, $userId, $remainingCost, self::BUCKET_PURCHASED);
            }

            return $transaction;
        });
    }

    public function refund(Store $store, string $type, AiGeneration $generation, ?int $userId = null): void
    {
        $cost = $this->cost($type);

        if ($cost < 1) {
            return;
        }

        $usageTransactions = AiCreditTransaction::where('ai_generation_id', $generation->id)
            ->where('type', AiCreditTransaction::TYPE_USAGE)
            ->get();

        if ($usageTransactions->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($store, $type, $generation, $userId, $usageTransactions) {
            $refundedUsageIds = AiCreditTransaction::where('ai_generation_id', $generation->id)
                ->where('type', AiCreditTransaction::TYPE_REFUND)
                ->get()
                ->pluck('metadata')
                ->map(fn ($metadata) => (int) ($metadata['usage_transaction_id'] ?? 0))
                ->filter()
                ->all();

            foreach ($usageTransactions as $usageTransaction) {
                if (in_array((int) $usageTransaction->id, $refundedUsageIds, true)) {
                    continue;
                }

                AiCreditTransaction::create([
                    'store_id' => $store->id,
                    'user_id' => $userId,
                    'ai_generation_id' => $generation->id,
                    'type' => AiCreditTransaction::TYPE_REFUND,
                    'amount' => abs((int) $usageTransaction->amount),
                    'reason' => 'Devolucion por generacion fallida',
                    'package_key' => $usageTransaction->package_key,
                    'metadata' => [
                        'ai_type' => $type,
                        'usage_transaction_id' => $usageTransaction->id,
                    ],
                ]);
            }
        });
    }

    public function addPackage(Store $store, string $packageKey, ?int $userId = null): AiCreditTransaction
    {
        if (! $store->allowsAiContent()) {
            throw new RuntimeException('Los paquetes de creditos IA estan disponibles solo en tiendas Premium.');
        }

        $package = self::PACKAGES[$packageKey] ?? null;

        if (! $package) {
            throw new RuntimeException('Paquete IA no valido.');
        }

        return AiCreditTransaction::create([
            'store_id' => $store->id,
            'user_id' => $userId,
            'type' => AiCreditTransaction::TYPE_PACKAGE_PURCHASE,
            'amount' => $package['credits'],
            'reason' => $package['label'],
            'package_key' => $packageKey,
            'price_cop' => $package['price_cop'],
        ]);
    }

    private function grantMonthlyPremiumCredits(Store $store): void
    {
        if (! $store->allowsAiContent()) {
            return;
        }

        try {
            AiCreditTransaction::firstOrCreate([
                'store_id' => $store->id,
                'type' => AiCreditTransaction::TYPE_MONTHLY_GRANT,
                'period' => now()->format('Y-m'),
            ], [
                'amount' => self::MONTHLY_PREMIUM_CREDITS,
                'reason' => 'Creditos IA mensuales Premium',
            ]);
        } catch (QueryException $exception) {
            if ((string) $exception->getCode() !== '23000') {
                throw $exception;
            }

            // Another request may have granted the monthly credits first.
        }
    }

    private function usageTransaction(
        Store $store,
        string $type,
        AiGeneration $generation,
        ?int $userId,
        int $amount,
        string $bucket,
    ): AiCreditTransaction {
        return AiCreditTransaction::create([
            'store_id' => $store->id,
            'user_id' => $userId,
            'ai_generation_id' => $generation->id,
            'type' => AiCreditTransaction::TYPE_USAGE,
            'amount' => -$amount,
            'reason' => $this->labelFor($type),
            'period' => null,
            'package_key' => $bucket,
            'metadata' => ['ai_type' => $type],
        ]);
    }

    private function availableBalance(Store $store): int
    {
        return max(0, $this->monthlyBalance($store)) + max(0, $this->purchasedBalance($store));
    }

    private function monthlyBalance(Store $store): int
    {
        $period = now()->format('Y-m');

        return (int) AiCreditTransaction::where('store_id', $store->id)
            ->where(function ($query) use ($period) {
                $query
                    ->where(function ($monthlyGrant) use ($period) {
                        $monthlyGrant
                            ->where('type', AiCreditTransaction::TYPE_MONTHLY_GRANT)
                            ->where('period', $period);
                    })
                    ->orWhere(function ($monthlyUsage) use ($period) {
                        $monthlyUsage
                            ->where('package_key', $this->monthlyBucket($period))
                            ->whereIn('type', [
                                AiCreditTransaction::TYPE_USAGE,
                                AiCreditTransaction::TYPE_REFUND,
                            ]);
                    });
            })
            ->sum('amount');
    }

    private function purchasedBalance(Store $store): int
    {
        return (int) AiCreditTransaction::where('store_id', $store->id)
            ->where(function ($query) {
                $query
                    ->where('type', AiCreditTransaction::TYPE_PACKAGE_PURCHASE)
                    ->orWhere('type', AiCreditTransaction::TYPE_ADJUSTMENT)
                    ->orWhere(function ($purchasedUsage) {
                        $purchasedUsage
                            ->where('package_key', self::BUCKET_PURCHASED)
                            ->whereIn('type', [
                                AiCreditTransaction::TYPE_USAGE,
                                AiCreditTransaction::TYPE_REFUND,
                            ]);
                    });
            })
            ->sum('amount');
    }

    private function monthlyBucket(?string $period = null): string
    {
        return self::BUCKET_MONTHLY . ':' . ($period ?: now()->format('Y-m'));
    }

    private function labelFor(string $type): string
    {
        return match ($type) {
            AiContentService::PRODUCT_NAME => 'Mejorar nombre de producto',
            AiContentService::PRODUCT_BADGES => 'Sugerir etiquetas',
            AiContentService::PRODUCT_FEATURES => 'Generar caracteristicas de producto',
            AiContentService::ANNOUNCEMENT => 'Crear avisos promocionales',
            AiContentService::PRODUCT_DESCRIPTION => 'Generar descripcion de producto',
            AiContentService::PRODUCT_IMAGE => 'Crear imagen ecommerce',
            AiContentService::STORE_COVER_IMAGE => 'Crear portada de tienda',
            default => 'Uso de IA',
        };
    }
}
