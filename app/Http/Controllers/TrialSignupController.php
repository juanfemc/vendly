<?php

namespace App\Http\Controllers;

use App\Exceptions\TrialPhoneHashConfigurationException;
use App\Http\Requests\TrialSignupRequest;
use App\Models\Store;
use App\Models\TrialSignupClaim;
use App\Models\User;
use App\Services\AdminUpdateService;
use App\Services\StoreSlugService;
use App\Services\TrialPhoneHashService;
use App\Services\TurnstileService;
use App\Services\WhatsAppPhoneVerificationService;
use App\Services\WhatsAppRegistrationNotifier;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        private WhatsAppRegistrationNotifier $whatsAppRegistrationNotifier,
        private WhatsAppPhoneVerificationService $phoneVerification,
        private TrialPhoneHashService $trialPhoneHashes,
        private TurnstileService $turnstile,
    ) {}

    public function create(): View
    {
        return view('auth.trial-signup', [
            'trialDays' => Store::TRIAL_DAYS,
            'requiresWhatsAppVerification' => $this->phoneVerification->isRequired(),
            'turnstileSiteKey' => $this->turnstile->siteKey(),
            'requiresTurnstile' => $this->turnstile->isRequired(),
            'turnstileReady' => $this->turnstile->isReady(),
        ]);
    }

    public function store(TrialSignupRequest $request): RedirectResponse
    {
        $phoneHash = $this->trialPhoneHash($request->validated('whatsapp'));

        [$user, $store] = $this->phoneVerification->runVerified(
            $request->validated('whatsapp'),
            $request->validated('whatsapp_verification_token'),
            $request->validated('whatsapp_verification_code'),
            function () use ($request, $phoneHash) {
                if (TrialSignupClaim::where('phone_hash', $phoneHash)->exists()) {
                    throw ValidationException::withMessages([
                        'whatsapp' => 'Este numero ya utilizo su prueba gratis.',
                    ]);
                }

                try {
                    return DB::transaction(function () use ($request, $phoneHash) {
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
                            ...$request->storeData($this->storeSlugs->uniqueFrom($request->validated('store_name'))),
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
            },
        );

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

        Auth::login($user);

        return redirect()
            ->route('admin.store.onboarding')
            ->with('success', 'Tu tienda fue creada. Tienes '.Store::TRIAL_DAYS.' dias de prueba gratis.');
    }

    public function sendVerificationCode(Request $request): JsonResponse
    {
        $phone = preg_replace('/\D+/', '', (string) $request->input('whatsapp')) ?: '';

        if (strlen($phone) === 10 && str_starts_with($phone, '3')) {
            $phone = '57'.$phone;
        }

        validator(['whatsapp' => $phone], [
            'whatsapp' => ['required', 'regex:/^573\d{9}$/'],
        ])->validate();

        if (! $this->phoneVerification->isRequired()) {
            return response()->json(['message' => 'La verificacion no es necesaria en este entorno.']);
        }

        try {
            $this->turnstile->verify((string) $request->input('turnstile_token'), $request->ip());

            $token = $this->phoneVerification->send(
                $phone,
                fn () => ! TrialSignupClaim::where('phone_hash', $this->trialPhoneHash($phone))->exists(),
                (string) ($request->ip() ?: 'unknown'),
            );
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::warning('No se pudo enviar el codigo de verificacion por WhatsApp.', [
                'exception' => $exception::class,
            ]);

            return response()->json([
                'message' => 'No pudimos enviar el codigo. Intenta nuevamente en unos minutos.',
            ], 422);
        }

        return response()->json([
            'message' => 'Si el numero es elegible, recibiras un codigo por WhatsApp.',
            'verification_token' => $token,
        ]);
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
