<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_generations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 60);
            $table->text('prompt')->nullable();
            $table->json('context')->nullable();
            $table->json('response')->nullable();
            $table->string('status', 30)->default('completed');
            $table->string('provider', 40)->default('openai');
            $table->string('model', 80)->nullable();
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'type']);
            $table->index(['store_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_generations');
    }
};
