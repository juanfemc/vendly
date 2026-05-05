<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->string('background_color', 7)->nullable()->after('brand_color');
            $table->string('text_color', 7)->nullable()->after('background_color');
            $table->string('font_family', 40)->nullable()->after('text_color');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn(['background_color', 'text_color', 'font_family']);
        });
    }
};
