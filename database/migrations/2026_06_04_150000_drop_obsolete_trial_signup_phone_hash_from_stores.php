<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropUnique(['trial_signup_phone_hash']);
            $table->dropColumn('trial_signup_phone_hash');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->string('trial_signup_phone_hash', 64)->nullable()->unique();
        });
    }
};
