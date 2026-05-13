<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('store_payment_accounts')) {
            return;
        }

        Schema::create('store_payment_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('provider');
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->text('public_key')->nullable();
            $table->string('provider_user_id')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('disconnected_at')->nullable();
            $table->string('status')->default('disconnected');
            $table->timestamps();

            $table->unique(['store_id', 'provider']);
            $table->index(['provider', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_payment_accounts');
    }
};
