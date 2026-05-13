<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'payment_method')) {
                $table->string('payment_method')->default('whatsapp')->after('status');
            }

            if (! Schema::hasColumn('orders', 'payment_provider')) {
                $table->string('payment_provider')->nullable()->after('payment_method');
            }

            if (! Schema::hasColumn('orders', 'payment_status')) {
                $table->string('payment_status')->default('not_required')->after('payment_provider');
            }

            if (! Schema::hasColumn('orders', 'payment_provider_reference')) {
                $table->string('payment_provider_reference')->nullable()->after('payment_status');
            }

            if (! Schema::hasColumn('orders', 'payment_preference_id')) {
                $table->string('payment_preference_id')->nullable()->after('payment_provider_reference');
            }

            if (! Schema::hasColumn('orders', 'payment_id')) {
                $table->string('payment_id')->nullable()->after('payment_preference_id');
            }

            if (! Schema::hasColumn('orders', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('payment_id');
            }

            if (! Schema::hasColumn('orders', 'payment_expires_at')) {
                $table->timestamp('payment_expires_at')->nullable()->after('paid_at');
            }
        });
    }

    public function down(): void
    {
        // No-op: these columns may have been created by the earlier payment-fields migration
        // in fresh installs. Dropping them here would break rollback order in those databases.
    }
};
