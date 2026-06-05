<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->timestamp('whatsapp_consent_at')->nullable()->after('whatsapp');
            $table->string('whatsapp_consent_version', 60)->nullable()->after('whatsapp_consent_at');
        });

        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('audience', 30);
            $table->string('template', 120);
            $table->string('recipient_hash', 64);
            $table->text('recipient');
            $table->text('parameters');
            $table->string('fingerprint', 64)->unique();
            $table->string('status', 30)->default('queued');
            $table->string('provider_message_id')->nullable()->unique();
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');

        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn(['whatsapp_consent_at', 'whatsapp_consent_version']);
        });
    }
};
