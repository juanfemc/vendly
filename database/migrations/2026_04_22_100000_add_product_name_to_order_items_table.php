<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('product_name')->nullable()->after('product_id');
        });

        DB::table('order_items')
            ->whereNull('product_name')
            ->whereNotNull('product_id')
            ->orderBy('id')
            ->chunkById(100, function ($items) {
                $productNames = DB::table('products')
                    ->whereIn('id', $items->pluck('product_id')->filter()->unique()->all())
                    ->pluck('name', 'id');

                foreach ($items as $item) {
                    $productName = $productNames[$item->product_id] ?? null;

                    if ($productName) {
                        DB::table('order_items')
                            ->where('id', $item->id)
                            ->update(['product_name' => $productName]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('product_name');
        });
    }
};
