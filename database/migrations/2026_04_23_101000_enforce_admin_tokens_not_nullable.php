<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    private array $tables = [
        'users',
        'stores',
        'products',
        'orders',
        'store_banners',
        'store_categories',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            DB::table($table)
                ->where(function ($query) {
                    $query->whereNull('admin_token')->orWhere('admin_token', '');
                })
                ->orderBy('id')
                ->chunkById(100, function ($rows) use ($table) {
                    foreach ($rows as $row) {
                        DB::table($table)
                            ->where('id', $row->id)
                            ->update(['admin_token' => (string) Str::uuid()]);
                    }
                });

            Schema::table($table, function (Blueprint $table) {
                $table->uuid('admin_token')->nullable(false)->change();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->uuid('admin_token')->nullable()->change();
            });
        }
    }
};
