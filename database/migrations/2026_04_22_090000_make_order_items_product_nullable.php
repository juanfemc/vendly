<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        $foreignKey = $this->foreignKeyName();

        if ($foreignKey) {
            DB::statement("ALTER TABLE order_items DROP FOREIGN KEY {$foreignKey}");
        }

        DB::statement('ALTER TABLE order_items MODIFY product_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE order_items ADD CONSTRAINT order_items_product_id_foreign FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL');
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        $foreignKey = $this->foreignKeyName();

        if ($foreignKey) {
            DB::statement("ALTER TABLE order_items DROP FOREIGN KEY {$foreignKey}");
        }

        DB::statement('ALTER TABLE order_items MODIFY product_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE order_items ADD CONSTRAINT order_items_product_id_foreign FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE');
    }

    private function foreignKeyName(): ?string
    {
        $database = DB::getDatabaseName();
        $result = DB::selectOne(
            'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME = ? LIMIT 1',
            [$database, 'order_items', 'product_id', 'products']
        );

        return $result?->CONSTRAINT_NAME;
    }
};
