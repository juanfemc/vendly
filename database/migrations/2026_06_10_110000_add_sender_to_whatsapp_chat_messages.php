<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('whatsapp_chat_messages') || Schema::hasColumn('whatsapp_chat_messages', 'sent_by_user_id')) {
            return;
        }

        Schema::table('whatsapp_chat_messages', function (Blueprint $table) {
            $table->foreignId('sent_by_user_id')
                ->nullable()
                ->after('store_id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('whatsapp_chat_messages') || ! Schema::hasColumn('whatsapp_chat_messages', 'sent_by_user_id')) {
            return;
        }

        Schema::table('whatsapp_chat_messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sent_by_user_id');
        });
    }
};
