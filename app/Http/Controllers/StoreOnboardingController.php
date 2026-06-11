<?php

namespace App\Http\Controllers;

use App\Exceptions\TrialPhoneHashConfigurationException;
use App\Http\Requests\StoreOnboardingRequest;
use App\Models\Store;
use App\Models\TrialSignupClaim;
use App\Services\AdminUpdateService;
use App\Services\StoreFileService;
use App\Services\TrialPhoneHashService;
use App\Services\WhatsAppPhoneVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StoreOnboardingController extends Controller
{
    public function __construct(
        private StoreFileService $storeFileService,
        private AdminUpdateService $adminUpdateService,
        private WhatsAppPhoneVerificationService $phoneVerification,
        private TrialPhoneHashService $trialPhoneHashes,
    ) {
    }

    public function edit(): View
    {
        $store = $this->currentStore();

        return view('admin.stores.onboarding', [
            'store' => $store,
            'checklist' => $store->onboardingChecklist(),
            'progress' => $store->onboardingProgress(),
        ]);
    }

    public function update(StoreOnboardingRequest $request): RedirectResponse
    {
        $store = $this->currentStore();
        $previousWhatsApp = (string) $store->whatsapp;

        $data = $this->storeFileService->replaceUploadedImages(
            $store,
            $request,
            $request->onboardingData()
        );

        if ($previousWhatsApp !== (string) ($data['whatsapp'] ?? '')) {
            $this->ensurePhoneIsAvailableForStore((string) $data['whatsapp'], $store);
            $data['whatsapp_verified_at'] = null;
        }

        $store->update($data);

        $this->adminUpdateService->record(
            'Onboarding actualizado',
            $store->name,
            'tienda',
            route('admin.store.onboarding')
        );

        return redirect()
            ->route('dashboard')
            ->with('success', 'Primeros pasos guardados. Ahora puedes agregar productos.');
    }

    public function sendWhatsAppVerificationCode(Request $request): JsonResponse
    {
        $store = $this->currentStore();
        $phone = $this->normalizedPhone((string) $request->input('whatsapp', $store->whatsapp));

        validator(['whatsapp' => $phone], [
            'whatsapp' => ['required', 'regex:/^573\d{9}$/'],
        ])->validate();

        if (! $this->phoneVerification->isRequired()) {
            $this->ensurePhoneIsAvailableForStore($phone, $store);

            $store->forceFill([
                'whatsapp' => $phone,
                'whatsapp_verified_at' => now(),
            ])->save();

            return response()->json(['message' => 'WhatsApp marcado como verificado en este entorno.']);
        }

        $phoneHash = $this->trialPhoneHash($phone);
        $token = $this->phoneVerification->send(
            $phone,
            fn () => $this->phoneIsAvailableForStore($phoneHash, $store),
            (string) ($request->ip() ?: 'unknown'),
        );

        return response()->json([
            'message' => 'Te enviamos un codigo por WhatsApp.',
            'verification_token' => $token,
        ]);
    }

    public function verifyWhatsApp(Request $request): JsonResponse
    {
        $store = $this->currentStore();
        $phone = $this->normalizedPhone((string) $request->input('whatsapp', $store->whatsapp));

        $validated = validator([
            'whatsapp' => $phone,
            'whatsapp_verification_code' => $request->input('whatsapp_verification_code'),
            'whatsapp_verification_token' => $request->input('whatsapp_verification_token'),
        ], [
            'whatsapp' => ['required', 'regex:/^573\d{9}$/'],
            'whatsapp_verification_code' => ['required', 'digits:6'],
            'whatsapp_verification_token' => ['required', 'string', 'size:64'],
        ])->validate();

        $this->phoneVerification->runVerified(
            $phone,
            $validated['whatsapp_verification_token'],
            $validated['whatsapp_verification_code'],
            function () use ($store, $phone) {
                DB::transaction(function () use ($store, $phone) {
                    $phoneHash = $this->trialPhoneHash($phone);
                    $claim = TrialSignupClaim::where('phone_hash', $phoneHash)->first();

                    if ($claim && (int) $claim->store_id !== (int) $store->id) {
                        throw ValidationException::withMessages([
                            'whatsapp' => 'Este numero ya utilizo su prueba gratis.',
                        ]);
                    }

                    TrialSignupClaim::updateOrCreate(
                        ['phone_hash' => $phoneHash],
                        [
                            'store_id' => $store->id,
                            'source' => 'trial_signup',
                            'claimed_at' => now(),
                        ],
                    );

                    $store->forceFill([
                        'whatsapp' => $phone,
                        'whatsapp_verified_at' => now(),
                    ])->save();
                });
            },
        );

        return response()->json(['message' => 'WhatsApp verificado correctamente.']);
    }

    private function currentStore(): Store
    {
        $store = auth()->user()?->store ?? auth()->user()?->stores()->first();

        abort_if(! $store, 404);
        $this->authorize('update', $store);

        return $store;
    }

    private function normalizedPhone(string $phone): string
    {
        $phone = preg_replace('/\D+/', '', $phone) ?: '';

        if (strlen($phone) === 10 && str_starts_with($phone, '3')) {
            $phone = '57'.$phone;
        }

        return $phone;
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
                'whatsapp' => 'No podemos verificar WhatsApp temporalmente. Intenta nuevamente mas tarde.',
            ]);
        }
    }

    private function ensurePhoneIsAvailableForStore(string $phone, Store $store): void
    {
        $phoneHash = $this->trialPhoneHash($phone);

        if (! $this->phoneIsAvailableForStore($phoneHash, $store)) {
            throw ValidationException::withMessages([
                'whatsapp' => 'Este numero ya utilizo su prueba gratis.',
            ]);
        }
    }

    private function phoneIsAvailableForStore(string $phoneHash, Store $store): bool
    {
        $claim = TrialSignupClaim::where('phone_hash', $phoneHash)->first();

        return ! $claim || (int) $claim->store_id === (int) $store->id;
    }
}
