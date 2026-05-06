<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->json('reservation_available_days')->nullable()->after('business_hours');
            $table->string('reservation_time_start', 5)->nullable()->after('reservation_available_days');
            $table->string('reservation_time_end', 5)->nullable()->after('reservation_time_start');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn([
                'reservation_available_days',
                'reservation_time_start',
                'reservation_time_end',
            ]);
        });
    }
};
