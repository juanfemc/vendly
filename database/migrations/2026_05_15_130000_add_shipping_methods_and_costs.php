<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->json('shipping_methods')->nullable()->after('free_shipping_minimum');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('shipping_method')->nullable()->after('notes');
            $table->decimal('shipping_cost', 12, 2)->default(0)->after('shipping_method');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['shipping_method', 'shipping_cost']);
        });

        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn('shipping_methods');
        });
    }
};
