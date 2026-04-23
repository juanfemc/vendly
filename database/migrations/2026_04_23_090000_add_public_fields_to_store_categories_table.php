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
        Schema::table('store_categories', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name');
            $table->text('description')->nullable()->after('slug');
            $table->string('image')->nullable()->after('description');
            $table->boolean('is_active')->default(true)->after('image');
            $table->unsignedInteger('sort_order')->default(0)->after('is_active');
        });

        DB::table('store_categories')
            ->select(['id', 'store_id', 'name', 'slug'])
            ->where(function ($query) {
                $query->whereNull('slug')->orWhere('slug', '');
            })
            ->orderBy('id')
            ->chunkById(100, function ($categories) {
                foreach ($categories as $category) {
                    $baseSlug = Str::slug($category->name) ?: 'categoria';
                    $slug = $baseSlug;
                    $counter = 2;

                    while (
                        DB::table('store_categories')
                            ->where('store_id', $category->store_id)
                            ->where('slug', $slug)
                            ->where('id', '!=', $category->id)
                            ->exists()
                    ) {
                        $slug = $baseSlug . '-' . $counter;
                        $counter++;
                    }

                    DB::table('store_categories')
                        ->where('id', $category->id)
                        ->update([
                            'slug' => $slug,
                            'is_active' => true,
                            'sort_order' => 0,
                        ]);
                }
            });

        Schema::table('store_categories', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->change();
            $table->unique(['store_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::table('store_categories', function (Blueprint $table) {
            $table->dropUnique(['store_id', 'slug']);
            $table->dropColumn(['slug', 'description', 'image', 'is_active', 'sort_order']);
        });
    }
};
