<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->date('active_starts_at')->nullable()->after('is_active');
            $table->unsignedInteger('active_duration_days')->nullable()->after('active_starts_at');
            $table->date('active_ends_at')->nullable()->after('active_duration_days');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'active_starts_at',
                'active_duration_days',
                'active_ends_at',
            ]);
        });
    }
};
