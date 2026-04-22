<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->string('logo_image')->nullable()->after('cover_image');
            $table->string('brand_color')->nullable()->after('logo_image');
            $table->string('instagram_url')->nullable()->after('brand_color');
            $table->string('facebook_url')->nullable()->after('instagram_url');
            $table->string('tiktok_url')->nullable()->after('facebook_url');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn([
                'logo_image',
                'brand_color',
                'instagram_url',
                'facebook_url',
                'tiktok_url',
            ]);
        });
    }
};
