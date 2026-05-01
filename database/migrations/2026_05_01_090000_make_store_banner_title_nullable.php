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

        DB::statement('ALTER TABLE store_banners MODIFY title VARCHAR(255) NULL');
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("UPDATE store_banners SET title = '' WHERE title IS NULL");
        DB::statement('ALTER TABLE store_banners MODIFY title VARCHAR(255) NOT NULL');
    }
};
