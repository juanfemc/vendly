<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->string('local_delivery_area')->nullable()->after('shipping_methods');
            $table->decimal('local_delivery_cost', 12, 2)->nullable()->after('local_delivery_area');
            $table->decimal('outside_delivery_cost', 12, 2)->nullable()->after('local_delivery_cost');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn([
                'local_delivery_area',
                'local_delivery_cost',
                'outside_delivery_cost',
            ]);
        });
    }
};
