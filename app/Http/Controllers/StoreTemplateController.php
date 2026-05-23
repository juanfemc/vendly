<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Services\AdminUpdateService;
use App\Support\StoreTemplateCatalog;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StoreTemplateController extends Controller
{
    public function __construct(private readonly AdminUpdateService $adminUpdateService)
    {
    }

    public function index(Request $request): View
    {
        $stores = $this->availableStores();
        $store = $this->storeForRequest($request, $stores);

        abort_unless($store, $stores->isEmpty() ? 403 : 404);

        $templates = StoreTemplateCatalog::all();

        return view('admin.templates.index', compact('store', 'stores', 'templates'));
    }

    public function apply(Request $request, string $template): RedirectResponse
    {
        $stores = $this->availableStores();
        $store = $this->storeForRequest($request, $stores);
        $templateData = StoreTemplateCatalog::find($template);

        abort_unless($store, $stores->isEmpty() ? 403 : 404);
        abort_unless($templateData, 404);

        $store->update(['business_type' => $templateData['business_type']]);
        $store->ensureCategoryRecords();

        $this->adminUpdateService->record(
            'Plantilla aplicada',
            $templateData['name'] . ' en ' . $store->name,
            'plantilla',
            route('admin.templates.index')
        );

        return redirect()
            ->route('admin.templates.index', ['store_id' => $store->id])
            ->with('success', 'Plantilla ' . $templateData['name'] . ' aplicada correctamente.');
    }

    private function availableStores(): Collection
    {
        $user = auth()->user();

        if (! $user) {
            return Store::newCollection();
        }

        $stores = $user->isAdmin()
            ? Store::orderBy('name')->get()
            : $user->stores()->orderBy('name')->get();

        return $stores->filter(fn (Store $store) => $store->allowsTemplates())->values();
    }

    private function storeForRequest(Request $request, Collection $stores): ?Store
    {
        if ($request->filled('store_id')) {
            return $stores->firstWhere('id', $request->integer('store_id'));
        }

        $userStore = auth()->user()?->store;

        if ($userStore && $stores->contains('id', $userStore->id)) {
            return $userStore;
        }

        return $stores->first();
    }
}
