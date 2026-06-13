<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('whatsapp_chat_messages') || Schema::hasColumn('whatsapp_chat_messages', 'whatsapp_message_id')) {
            return;
        }

        Schema::table('whatsapp_chat_messages', function (Blueprint $table) {
            $table->foreignId('whatsapp_message_id')
                ->nullable()
                ->after('sent_by_user_id')
                ->constrained('whatsapp_messages')
                ->nullOnDelete();

            $table->unique('whatsapp_message_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('whatsapp_chat_messages') || ! Schema::hasColumn('whatsapp_chat_messages', 'whatsapp_message_id')) {
            return;
        }

        Schema::table('whatsapp_chat_messages', function (Blueprint $table) {
            $table->dropUnique(['whatsapp_message_id']);
            $table->dropConstrainedForeignId('whatsapp_message_id');
        });
    }
};
