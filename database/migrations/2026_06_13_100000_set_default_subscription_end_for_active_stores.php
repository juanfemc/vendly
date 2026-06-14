<?php

use App\Models\Store;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('stores')
            || ! Schema::hasTable('users')
            || ! Schema::hasColumn('stores', 'subscription_status')
            || ! Schema::hasColumn('stores', 'subscription_ends_at')
            || ! Schema::hasColumn('stores', 'user_id')
            || ! Schema::hasColumn('users', 'active_ends_at')) {
            return;
        }

        DB::table('stores')
            ->where(function ($query) {
                $query->where('subscription_status', Store::SUBSCRIPTION_ACTIVE)
                    ->orWhereNull('subscription_status');
            })
            ->whereNull('subscription_ends_at')
            ->select([
                'id',
                'user_id',
            ])
            ->chunkById(100, function ($stores) {
                $userEndsAt = DB::table('users')
                    ->whereIn('id', $stores->pluck('user_id')->filter()->unique()->values())
                    ->whereNotNull('active_ends_at')
                    ->pluck('active_ends_at', 'id');

                foreach ($stores as $store) {
                    $activeEndsAt = $userEndsAt->get($store->user_id);

                    if (! $activeEndsAt) {
                        continue;
                    }

                    DB::table('stores')
                        ->where('id', $store->id)
                        ->update([
                            'subscription_status' => Store::SUBSCRIPTION_ACTIVE,
                            'subscription_ends_at' => Carbon::parse($activeEndsAt)->endOfDay(),
                            'updated_at' => now(),
                        ]);
                }
            });
    }

    public function down(): void
    {
        // No se revierte para no borrar fechas reales de vencimiento.
    }
};
