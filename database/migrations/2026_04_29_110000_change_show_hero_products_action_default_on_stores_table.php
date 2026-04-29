<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE stores MODIFY show_hero_products_action TINYINT(1) NOT NULL DEFAULT 0');
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE stores MODIFY show_hero_products_action TINYINT(1) NOT NULL DEFAULT 1');
    }
};
