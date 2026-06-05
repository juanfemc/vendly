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

        if (! Schema::hasTable('trial_signup_claims')) {
            Schema::create('trial_signup_claims', function (Blueprint $table) {
                $table->id();
                $table->string('phone_hash', 64)->unique();
                $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
                $table->string('source', 60)->default('trial_signup');
                $table->timestamp('claimed_at');
                $table->timestamps();
            });
        }

        DB::table('stores')
            ->where(function ($query) {
                $query->where('whatsapp_consent_source', 'trial_signup')
                    ->orWhere(function ($legacyTrial) {
                        $legacyTrial->where('subscription_status', 'trialing')
                            ->whereNull('created_by_admin_id');
                    });
            })
            ->whereNotNull('whatsapp')
            ->orderBy('id')
            ->each(function ($store) use ($key) {
                $phone = preg_replace('/\D+/', '', (string) $store->whatsapp) ?: '';

                if (strlen($phone) === 10 && str_starts_with($phone, '3')) {
                    $phone = '57'.$phone;
                }

                if ($phone === '') {
                    return;
                }

                DB::table('trial_signup_claims')->insertOrIgnore([
                    'phone_hash' => hash_hmac('sha256', $phone, $key),
                    'store_id' => $store->id,
                    'source' => 'historical_backfill',
                    'claimed_at' => $store->trial_starts_at ?: $store->created_at,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('trial_signup_claims');
    }
};
