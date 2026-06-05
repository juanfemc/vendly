<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $key = (string) config('services.trial.phone_hash_key');

        if ($key === '') {
            throw new RuntimeException('TRIAL_PHONE_HASH_KEY no esta configurada.');
        }

        if (! Schema::hasTable('trial_signup_key_guards')) {
            Schema::create('trial_signup_key_guards', function (Blueprint $table) {
                $table->unsignedTinyInteger('id')->primary();
                $table->string('key_fingerprint', 64);
                $table->timestamps();
            });
        }

        $fingerprint = hash('sha256', $key);

        DB::table('trial_signup_key_guards')->insertOrIgnore([
            'id' => 1,
            'key_fingerprint' => $fingerprint,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $storedFingerprint = DB::table('trial_signup_key_guards')->where('id', 1)->value('key_fingerprint');

        if (! is_string($storedFingerprint) || ! hash_equals($storedFingerprint, $fingerprint)) {
            throw new RuntimeException('TRIAL_PHONE_HASH_KEY no coincide con la clave registrada.');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('trial_signup_key_guards');
    }
};
