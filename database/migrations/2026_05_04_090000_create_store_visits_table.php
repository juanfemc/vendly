<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('ip_hash', 64);
            $table->string('user_agent_hash', 64);
            $table->date('visited_on');
            $table->timestamp('visited_at')->useCurrent();
            $table->timestamps();

            $table->unique(['store_id', 'ip_hash', 'user_agent_hash', 'visited_on'], 'store_visits_unique_daily_visitor');
            $table->index(['store_id', 'visited_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_visits');
    }
};
