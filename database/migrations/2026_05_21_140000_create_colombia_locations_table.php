<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('colombia_locations', function (Blueprint $table) {
            $table->id();
            $table->string('department_code', 8);
            $table->string('department_name', 120);
            $table->string('city_code', 12)->unique();
            $table->string('city_name', 160);
            $table->string('normalized_city_name', 160);
            $table->timestamps();

            $table->index(['department_code', 'city_name']);
            $table->index('normalized_city_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('colombia_locations');
    }
};
