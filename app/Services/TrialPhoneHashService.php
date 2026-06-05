<?php

namespace App\Services;

use App\Exceptions\TrialPhoneHashConfigurationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TrialPhoneHashService
{
    public function make(string $phone): string
    {
        $key = (string) config('services.trial.phone_hash_key');

        if ($key === '') {
            throw new TrialPhoneHashConfigurationException('TRIAL_PHONE_HASH_KEY no esta configurada.');
        }

        if (Schema::hasTable('trial_signup_claims')) {
            if (! Schema::hasTable('trial_signup_key_guards')) {
                throw new TrialPhoneHashConfigurationException('Falta la guardia de TRIAL_PHONE_HASH_KEY.');
            }

            $expected = DB::table('trial_signup_key_guards')->where('id', 1)->value('key_fingerprint');
            $provided = hash('sha256', $key);

            if (! is_string($expected) || ! hash_equals($expected, $provided)) {
                throw new TrialPhoneHashConfigurationException('TRIAL_PHONE_HASH_KEY no coincide con la clave registrada.');
            }
        }

        return hash_hmac('sha256', $phone, $key);
    }
}
