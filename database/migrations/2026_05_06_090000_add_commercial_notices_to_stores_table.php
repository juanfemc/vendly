<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->json('announcement_items')->nullable()->after('business_hours');
            $table->decimal('free_shipping_minimum', 12, 2)->nullable()->after('announcement_items');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn([
                'announcement_items',
                'free_shipping_minimum',
            ]);
        });
    }
};
