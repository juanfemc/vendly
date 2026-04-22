<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_banners', function (Blueprint $table) {
            $table->string('group_token')->nullable()->after('store_id');
            $table->boolean('applies_to_all')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('store_banners', function (Blueprint $table) {
            $table->dropColumn(['group_token', 'applies_to_all']);
        });
    }
};
