<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name');
        });

        $usedSlugsByStore = [];

        DB::table('products')
            ->select(['id', 'store_id', 'name'])
            ->orderBy('id')
            ->chunkById(100, function ($products) use (&$usedSlugsByStore) {
                foreach ($products as $product) {
                    $storeId = (int) $product->store_id;
                    $token = substr(sha1($product->id . '|' . $product->store_id . '|' . $product->name . '|' . config('app.key')), 0, 8);
                    $baseSlug = (Str::slug($product->name) ?: 'producto') . '-' . $token;
                    $slug = $baseSlug;
                    $counter = 2;

                    while (in_array($slug, $usedSlugsByStore[$storeId] ?? [], true)) {
                        $slug = $baseSlug . '-' . $counter;
                        $counter++;
                    }

                    $usedSlugsByStore[$storeId][] = $slug;

                    DB::table('products')
                        ->where('id', $product->id)
                        ->update(['slug' => $slug]);
                }
            });

        Schema::table('products', function (Blueprint $table) {
            $table->unique(['store_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['store_id', 'slug']);
            $table->dropColumn('slug');
        });
    }
};
