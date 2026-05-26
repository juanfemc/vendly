<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            if (! Schema::hasColumn('stores', 'meta_pixel_id')) {
                $table->string('meta_pixel_id', 50)->nullable()->after('tiktok_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            if (Schema::hasColumn('stores', 'meta_pixel_id')) {
                $table->dropColumn('meta_pixel_id');
            }
        });
    }
};
