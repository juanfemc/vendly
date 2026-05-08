<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('stores', 'subdomain')) {
            return;
        }

        Schema::table('stores', function (Blueprint $table) {
            $table->string('subdomain')->nullable()->unique()->after('slug');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('stores', 'subdomain')) {
            return;
        }

        Schema::table('stores', function (Blueprint $table) {
            $table->dropUnique(['subdomain']);
            $table->dropColumn('subdomain');
        });
    }
};
