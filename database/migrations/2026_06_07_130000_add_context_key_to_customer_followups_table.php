<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_followups', function (Blueprint $table) {
            if (! Schema::hasColumn('customer_followups', 'context_key')) {
                $table->string('context_key', 120)->default('default')->after('type');
            }
        });

        try {
            Schema::table('customer_followups', function (Blueprint $table) {
                $table->dropUnique(['store_id', 'type']);
            });
        } catch (Throwable) {
            //
        }

        try {
            Schema::table('customer_followups', function (Blueprint $table) {
                $table->unique(['store_id', 'type', 'context_key']);
            });
        } catch (Throwable) {
            //
        }
    }

    public function down(): void
    {
        try {
            Schema::table('customer_followups', function (Blueprint $table) {
                $table->dropUnique(['store_id', 'type', 'context_key']);
            });
        } catch (Throwable) {
            //
        }

        if (! $this->hasStoreTypeDuplicates()) {
            try {
                Schema::table('customer_followups', function (Blueprint $table) {
                    $table->unique(['store_id', 'type']);
                });
            } catch (Throwable) {
                //
            }
        }
    }

    private function hasStoreTypeDuplicates(): bool
    {
        return DB::table('customer_followups')
            ->select('store_id', 'type')
            ->groupBy('store_id', 'type')
            ->havingRaw('COUNT(*) > 1')
            ->exists();
    }
};
