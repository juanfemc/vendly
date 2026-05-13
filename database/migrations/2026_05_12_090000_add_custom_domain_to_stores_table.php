<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            if (! Schema::hasColumn('stores', 'custom_domain')) {
                $table->string('custom_domain')->nullable()->unique()->after('subdomain');
            }

            if (! Schema::hasColumn('stores', 'custom_domain_status')) {
                $table->string('custom_domain_status')->default('pending')->after('custom_domain');
            }

            if (! Schema::hasColumn('stores', 'custom_domain_verified_at')) {
                $table->timestamp('custom_domain_verified_at')->nullable()->after('custom_domain_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            if (Schema::hasColumn('stores', 'custom_domain')) {
                $table->dropUnique(['custom_domain']);
            }

            foreach (['custom_domain_verified_at', 'custom_domain_status', 'custom_domain'] as $column) {
                if (Schema::hasColumn('stores', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
