<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('products')
            ->select(['id', 'store_id', 'name', 'slug'])
            ->where(function ($query) {
                $query
                    ->whereNull('slug')
                    ->orWhere('slug', '');
            })
            ->orderBy('id')
            ->chunkById(100, function ($products) {
                foreach ($products as $product) {
                    $token = substr(sha1($product->id . '|' . $product->store_id . '|' . $product->name . '|' . config('app.key')), 0, 8);
                    $baseSlug = (Str::slug($product->name) ?: 'producto') . '-' . $token;
                    $slug = $baseSlug;
                    $counter = 2;

                    while (
                        DB::table('products')
                            ->where('store_id', $product->store_id)
                            ->where('slug', $slug)
                            ->where('id', '!=', $product->id)
                            ->exists()
                    ) {
                        $slug = $baseSlug . '-' . $counter;
                        $counter++;
                    }

                    DB::table('products')
                        ->where('id', $product->id)
                        ->update(['slug' => $slug]);
                }
            });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE products MODIFY slug VARCHAR(255) NOT NULL');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE products MODIFY slug VARCHAR(255) NULL');
        }
    }
};
