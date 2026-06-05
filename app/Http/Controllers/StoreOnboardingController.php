<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOnboardingRequest;
use App\Models\Store;
use App\Services\AdminUpdateService;
use App\Services\StoreFileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class StoreOnboardingController extends Controller
{
    public function __construct(
        private StoreFileService $storeFileService,
        private AdminUpdateService $adminUpdateService,
    ) {
    }

    public function edit(): View
    {
        $store = $this->currentStore();

        return view('admin.stores.onboarding', [
            'store' => $store,
            'businessTypeOptions' => Store::businessTypeOptions(),
            'checklist' => $store->onboardingChecklist(),
            'progress' => $store->onboardingProgress(),
        ]);
    }

    public function update(StoreOnboardingRequest $request): RedirectResponse
    {
        $store = $this->currentStore();

        $store->update($this->storeFileService->replaceUploadedImages(
            $store,
            $request,
            $request->onboardingData()
        ));

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

    private function currentStore(): Store
    {
        $store = auth()->user()?->store ?? auth()->user()?->stores()->first();

        abort_if(! $store, 404);
        $this->authorize('update', $store);

        return $store;
    }
}
