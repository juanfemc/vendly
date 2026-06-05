<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_credit_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ai_generation_id')->nullable()->constrained('ai_generations')->nullOnDelete();
            $table->string('type', 40);
            $table->integer('amount');
            $table->string('reason')->nullable();
            $table->string('period', 7)->nullable();
            $table->string('package_key')->nullable();
            $table->unsignedInteger('price_cop')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'created_at']);
            $table->index(['store_id', 'type', 'period']);
            $table->index(['store_id', 'package_key']);
            $table->unique(['store_id', 'type', 'period'], 'ai_credit_monthly_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_credit_transactions');
    }
};
