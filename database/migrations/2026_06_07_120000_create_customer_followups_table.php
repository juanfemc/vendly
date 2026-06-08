<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_followups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('whatsapp_message_id')->nullable()->constrained('whatsapp_messages')->nullOnDelete();
            $table->string('type', 60);
            $table->string('context_key', 120)->default('default');
            $table->string('template', 120);
            $table->text('parameters');
            $table->string('status', 30)->default('pending');
            $table->timestamp('scheduled_for')->index();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('skipped_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'type', 'context_key']);
            $table->index(['status', 'scheduled_for']);
            $table->index(['store_id', 'status']);
            $table->index('whatsapp_message_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_followups');
    }
};
