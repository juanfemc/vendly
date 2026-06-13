<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('store_payment_accounts')) {
            return;
        }

        Schema::table('store_payment_accounts', function (Blueprint $table) {
            if (! Schema::hasColumn('store_payment_accounts', 'private_key')) {
                $table->text('private_key')->nullable()->after('public_key');
            }

            if (! Schema::hasColumn('store_payment_accounts', 'events_secret')) {
                $table->text('events_secret')->nullable()->after('private_key');
            }

            if (! Schema::hasColumn('store_payment_accounts', 'integrity_secret')) {
                $table->text('integrity_secret')->nullable()->after('events_secret');
            }

            if (! Schema::hasColumn('store_payment_accounts', 'mode')) {
                $table->string('mode')->default('sandbox')->after('integrity_secret');
            }

            if (! Schema::hasColumn('store_payment_accounts', 'settings')) {
                $table->json('settings')->nullable()->after('mode');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('store_payment_accounts')) {
            return;
        }

        Schema::table('store_payment_accounts', function (Blueprint $table) {
            foreach (['settings', 'mode', 'integrity_secret', 'events_secret', 'private_key'] as $column) {
                if (Schema::hasColumn('store_payment_accounts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
