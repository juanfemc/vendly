<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('stores')) {
            $this->addStoreColumn('require_terms_acceptance', fn (Blueprint $table) => $table->boolean('require_terms_acceptance')->default(false));
            $this->addStoreColumn('terms_title', fn (Blueprint $table) => $table->string('terms_title')->nullable());
            $this->addStoreColumn('terms_content', fn (Blueprint $table) => $table->text('terms_content')->nullable());
            $this->addStoreColumn('terms_url', fn (Blueprint $table) => $table->string('terms_url')->nullable());
            $this->addStoreColumn('terms_version', fn (Blueprint $table) => $table->string('terms_version', 80)->nullable());
        }

        if (Schema::hasTable('orders')) {
            $this->addOrderColumn('terms_accepted_at', fn (Blueprint $table) => $table->timestamp('terms_accepted_at')->nullable());
            $this->addOrderColumn('terms_version', fn (Blueprint $table) => $table->string('terms_version', 80)->nullable());
            $this->addOrderColumn('terms_snapshot', fn (Blueprint $table) => $table->text('terms_snapshot')->nullable());
            $this->addOrderColumn('terms_url', fn (Blueprint $table) => $table->string('terms_url')->nullable());
            $this->addOrderColumn('terms_ip_hash', fn (Blueprint $table) => $table->string('terms_ip_hash', 64)->nullable());
            $this->addOrderColumn('terms_user_agent_hash', fn (Blueprint $table) => $table->string('terms_user_agent_hash', 64)->nullable());
        }
    }

    public function down(): void
    {
        $orderColumns = $this->existingColumns('orders', [
            'terms_accepted_at',
            'terms_version',
            'terms_snapshot',
            'terms_url',
            'terms_ip_hash',
            'terms_user_agent_hash',
        ]);

        if ($orderColumns !== []) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn($this->existingColumns('orders', [
                    'terms_accepted_at',
                    'terms_version',
                    'terms_snapshot',
                    'terms_url',
                    'terms_ip_hash',
                    'terms_user_agent_hash',
                ]));
            });
        }

        $storeColumns = $this->existingColumns('stores', [
            'require_terms_acceptance',
            'terms_title',
            'terms_content',
            'terms_url',
            'terms_version',
        ]);

        if ($storeColumns !== []) {
            Schema::table('stores', function (Blueprint $table) {
                $table->dropColumn($this->existingColumns('stores', [
                    'require_terms_acceptance',
                    'terms_title',
                    'terms_content',
                    'terms_url',
                    'terms_version',
                ]));
            });
        }
    }

    private function addStoreColumn(string $column, callable $definition): void
    {
        if (! Schema::hasColumn('stores', $column)) {
            Schema::table('stores', $definition);
        }
    }

    private function addOrderColumn(string $column, callable $definition): void
    {
        if (! Schema::hasColumn('orders', $column)) {
            Schema::table('orders', $definition);
        }
    }

    private function existingColumns(string $table, array $columns): array
    {
        if (! Schema::hasTable($table)) {
            return [];
        }

        return array_values(array_filter(
            $columns,
            fn (string $column) => Schema::hasColumn($table, $column),
        ));
    }
};
