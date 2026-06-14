<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('customer_followups') && ! Schema::hasColumn('customer_followups', 'cancelled_at')) {
            Schema::table('customer_followups', function (Blueprint $table) {
                $table->timestamp('cancelled_at')->nullable()->after('skipped_at');
            });
        }

        if (Schema::hasTable('whatsapp_messages') && ! Schema::hasColumn('whatsapp_messages', 'cancelled_at')) {
            Schema::table('whatsapp_messages', function (Blueprint $table) {
                $table->timestamp('cancelled_at')->nullable()->after('failed_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('customer_followups') && Schema::hasColumn('customer_followups', 'cancelled_at')) {
            Schema::table('customer_followups', function (Blueprint $table) {
                $table->dropColumn('cancelled_at');
            });
        }

        if (Schema::hasTable('whatsapp_messages') && Schema::hasColumn('whatsapp_messages', 'cancelled_at')) {
            Schema::table('whatsapp_messages', function (Blueprint $table) {
                $table->dropColumn('cancelled_at');
            });
        }
    }
};
