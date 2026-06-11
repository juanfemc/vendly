<?php

namespace App\Http\Controllers;

use App\Exceptions\TrialPhoneHashConfigurationException;
use App\Http\Requests\TrialSignupRequest;
use App\Models\Store;
use App\Models\TrialSignupClaim;
use App\Models\User;
use App\Services\AdminUpdateService;
use App\Services\CustomerFollowupScheduler;
use App\Services\StoreSlugService;
use App\Services\TrialPhoneHashService;
use App\Services\TurnstileService;
use App\Services\WhatsAppRegistrationNotifier;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class TrialSignupController extends Controller
{
    public function __construct(
        private StoreSlugService $storeSlugs,
        private AdminUpdateService $adminUpdateService,
        private CustomerFollowupScheduler $customerFollowups,
        private WhatsAppRegistrationNotifier $whatsAppRegistrationNotifier,
        private TrialPhoneHashService $trialPhoneHashes,
        private TurnstileService $turnstile,
    ) {}

    public function create(): View
    {
        return view('auth.trial-signup', [
            'trialDays' => Store::TRIAL_DAYS,
            'turnstileSiteKey' => $this->turnstile->siteKey(),
            'requiresTurnstile' => $this->turnstile->isRequired(),
            'turnstileReady' => $this->turnstile->isReady(),
        ]);
    }

    public function store(TrialSignupRequest $request): RedirectResponse
    {
        $this->turnstile->verify((string) $request->validated('turnstile_token'), $request->ip());

        $phoneHash = $this->trialPhoneHash($request->validated('whatsapp'));
        $storeName = trim((string) $request->validated('store_name'));
        $storeNameForSlug = $storeName !== '' ? $storeName : 'Tienda de '.$request->validated('user_name');

        if (TrialSignupClaim::where('phone_hash', $phoneHash)->exists()) {
            throw ValidationException::withMessages([
                'whatsapp' => 'Este numero ya utilizo su prueba gratis.',
            ]);
        }

        try {
            [$user, $store] = DB::transaction(function () use ($request, $phoneHash, $storeNameForSlug) {
                $claim = TrialSignupClaim::create([
                    'phone_hash' => $phoneHash,
                    'source' => 'trial_signup',
                    'claimed_at' => now(),
                ]);

                $user = User::create([
                    ...$request->userData(),
                    'password' => Hash::make($request->validated('password')),
                ]);

                $store = Store::create([
                    ...$request->storeData($this->storeSlugs->uniqueFrom($storeNameForSlug)),
                    'user_id' => $user->id,
                ]);

                $store->startTrial();
                $claim->update(['store_id' => $store->id]);

                return [$user, $store];
            });
        } catch (QueryException $exception) {
            if (TrialSignupClaim::where('phone_hash', $phoneHash)->exists()) {
                throw ValidationException::withMessages([
                    'whatsapp' => 'Este numero ya utilizo su prueba gratis.',
                ]);
            }

            throw $exception;
        }

        event(new Registered($user));

        $this->adminUpdateService->record(
            'Prueba gratis creada',
            $store->name.' inicio una prueba de '.Store::TRIAL_DAYS.' dias',
            'tienda',
            route('admin.stores.edit', $store)
        );

        try {
            $this->whatsAppRegistrationNotifier->notify($user, $store);
        } catch (Throwable $exception) {
            Log::warning('No se pudieron programar los mensajes de registro por WhatsApp.', [
                'store_id' => $store->id,
                'exception' => $exception::class,
            ]);
        }

        try {
            $this->customerFollowups->scheduleForStore($store);
        } catch (Throwable $exception) {
            Log::warning('No se pudieron programar los seguimientos por WhatsApp.', [
                'store_id' => $store->id,
                'exception' => $exception::class,
            ]);
        }

        Auth::login($user);

        return redirect()
            ->route('admin.store.onboarding')
            ->with('success', 'Tu tienda ya esta creada. Verifica tu WhatsApp para completar la activacion.');
    }

    private function trialPhoneHash(string $phone): string
    {
        try {
            return $this->trialPhoneHashes->make($phone);
        } catch (TrialPhoneHashConfigurationException $exception) {
            Log::critical('La proteccion de pruebas gratis no esta disponible.', [
                'exception' => $exception::class,
            ]);

            throw ValidationException::withMessages([
                'whatsapp' => 'No podemos procesar pruebas gratis temporalmente. Intenta nuevamente mas tarde.',
            ]);
        }
    }
}
