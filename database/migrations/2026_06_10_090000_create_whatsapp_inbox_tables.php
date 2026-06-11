<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('conversation_key', 64)->unique();
            $table->string('contact_phone_hash', 64);
            $table->text('contact_phone');
            $table->text('contact_name')->nullable();
            $table->string('status', 30)->default('open');
            $table->unsignedInteger('unread_count')->default(0);
            $table->timestamp('last_customer_message_at')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'contact_phone_hash']);
            $table->index(['store_id', 'last_message_at']);
            $table->index(['status', 'last_message_at']);
        });

        Schema::create('whatsapp_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('whatsapp_conversations')->cascadeOnDelete();
            $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sent_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('direction', 20);
            $table->string('message_type', 30)->default('text');
            $table->text('body')->nullable();
            $table->text('media_id')->nullable();
            $table->string('provider_message_id')->nullable()->unique();
            $table->string('status', 30)->default('received');
            $table->text('error')->nullable();
            $table->text('payload')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
            $table->index(['store_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_chat_messages');
        Schema::dropIfExists('whatsapp_conversations');
    }
};
