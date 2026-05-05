<?php

namespace App\Services;

use App\Models\Store;
use App\Models\StoreVisit;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class StoreVisitService
{
    public function record(Store $store, Request $request): void
    {
        if (! Schema::hasTable('store_visits')) {
            $this->incrementLegacyCounter($store);

            return;
        }

        $visitData = [
            'store_id' => $store->id,
            'ip_hash' => $this->hashValue($request->ip()),
            'user_agent_hash' => $this->hashValue($request->userAgent()),
            'visited_on' => now()->toDateString(),
        ];

        if (StoreVisit::where($visitData)->exists()) {
            return;
        }

        try {
            StoreVisit::create($visitData + ['visited_at' => now()]);
            $this->incrementLegacyCounter($store);
        } catch (QueryException) {
            return;
        }
    }

    private function incrementLegacyCounter(Store $store): void
    {
        if (Schema::hasColumn('stores', 'views_count')) {
            $store->increment('views_count');
        }
    }

    private function hashValue(?string $value): string
    {
        if ($value === null || trim($value) === '') {
            $value = 'unknown';
        }

        return hash('sha256', $value);
    }
}
