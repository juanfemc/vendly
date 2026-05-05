<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->text('business_hours')->nullable()->after('location');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->date('reservation_date')->nullable()->after('customer_document');
            $table->string('reservation_time')->nullable()->after('reservation_date');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['reservation_date', 'reservation_time']);
        });

        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn('business_hours');
        });
    }
};
