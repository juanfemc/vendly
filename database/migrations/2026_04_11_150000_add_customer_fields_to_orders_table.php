<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('customer_address')->nullable()->after('customer_phone');
            $table->string('customer_city')->nullable()->after('customer_address');
            $table->string('customer_document')->nullable()->after('customer_city');
            $table->text('notes')->nullable()->after('customer_document');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'customer_address',
                'customer_city',
                'customer_document',
                'notes',
            ]);
        });
    }
};
