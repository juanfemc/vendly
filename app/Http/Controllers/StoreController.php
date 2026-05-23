<?php

namespace App\Http\Controllers;


use App\Http\Requests\StoreRequest;
use App\Http\Requests\StoreSettingsRequest;
use App\Models\ColombiaLocation;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Services\AdminUpdateService;
use App\Services\StoreFileService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;

class StoreController extends Controller
{
    public function __construct(
        private StoreFileService $storeFileService,
        private AdminUpdateService $adminUpdateService,
    ) {
    }

    public function index()
    {
        $this->authorize('create', Store::class);

        $stores = Store::with(['user', 'creatorAdmin'])->latest()->get();

        return view('admin.stores.index', compact('stores'));
    }

    public function create()
    {
        $this->authorize('create', Store::class);

        $users = User::where('role', 'store')
            ->whereDoesntHave('store')
            ->orderBy('name')
            ->get();

        return view('admin.stores.create', compact('users'));
    }

    public function visits()
    {
        if (! Schema::hasColumn('stores', 'views_count')) {
            $stores = new LengthAwarePaginator([], 0, 10);

            return view('admin.stores.visits', [
                'stores' => $stores,
                'totalVisits' => 0,
                'needsMigration' => true,
            ]);
        }

        if (! auth()->user()?->isAdmin()) {
            $store = auth()->user()?->store ?? auth()->user()?->stores()->first();

            abort_if(! $store, 404);
            abort_unless($store->allowsVisitStats(), 403);

            $stores = new LengthAwarePaginator(collect([$store]), 1, 10);

            return view('admin.stores.visits', [
                'stores' => $stores,
                'totalVisits' => (int) ($store->views_count ?? 0),
                'needsMigration' => false,
                'selectedStore' => $store,
            ]);
        }

        $this->authorize('create', Store::class);

        $stores = Store::with('user')
            ->where('views_count', '>', 0)
            ->orderByDesc('views_count')
            ->orderBy('name')
            ->paginate(10);
        $totalVisits = (int) Store::where('views_count', '>', 0)->sum('views_count');

        return view('admin.stores.visits', [
            'stores' => $stores,
            'totalVisits' => $totalVisits,
            'needsMigration' => false,
        ]);
    }

    public function store(StoreRequest $request)
    {
        $this->authorize('create', Store::class);

        $store = Store::create(array_merge($request->storeData(), $this->storeFileService->storeUploadedImages($request), [
            'created_by_admin_id' => auth()->id(),
        ]));

        $this->adminUpdateService->record(
            'Tienda creada',
            $store->name,
            'tienda',
            route('admin.stores.edit', $store)
        );

        return redirect('/admin/stores')->with('success', 'Tienda creada.');
    }

    public function edit(Store $store)
    {
        $this->authorize('update', $store);

        $users = User::where('role', 'store')
            ->where(function ($query) use ($store) {
                $query
                    ->whereDoesntHave('store')
                    ->orWhere('id', $store->user_id);
            })
            ->orderBy('name')
            ->get();

        return view('admin.stores.edit', compact('store', 'users'));
    }

    public function update(StoreRequest $request, Store $store)
    {
        $this->authorize('update', $store);

        $storeData = $request->storeData();
        $requestedPlan = $storeData['plan'] ?? $store->plan;

        if (! $this->storeCanUsePlan($store, $requestedPlan)) {
            return back()
                ->withInput()
                ->with('error', 'Esta tienda tiene mas productos que los permitidos por el plan ' . Store::planOptions()[$requestedPlan] . '.');
        }

        $store->update($this->storeFileService->replaceUploadedImages($store, $request, $storeData));
        $this->enforcePlanLimits($store);

        $this->adminUpdateService->record(
            'Tienda actualizada',
            $store->name,
            'tienda',
            route('admin.stores.edit', $store)
        );

        return redirect('/admin/stores')->with('success', 'Tienda actualizada.');
    }

    public function destroy(Store $store)
    {
        $this->authorize('update', $store);

        $this->storeFileService->deleteStoreFiles($store);

        $storeName = $store->name;
        $store->delete();

        $this->adminUpdateService->record('Tienda eliminada', $storeName, 'tienda');

        return redirect('/admin/stores')->with('success', 'Tienda eliminada.');
    }

    public function settings()
    {
        $store = auth()->user()?->store ?? auth()->user()?->stores()->first();

        abort_if(! $store, 404);
        $this->authorize('update', $store);

        $businessTypeOptions = Store::businessTypeOptions();
        $colombiaLocations = ColombiaLocation::citiesForSelect();

        return view('admin.stores.settings', compact('store', 'businessTypeOptions', 'colombiaLocations'));
    }

    public function updateSettings(StoreSettingsRequest $request)
    {
        $store = auth()->user()?->store ?? auth()->user()?->stores()->first();

        abort_if(! $store, 404);
        $this->authorize('update', $store);

        $store->update($this->storeFileService->replaceUploadedImages($store, $request, $request->settingsData()));
        $this->enforcePlanLimits($store);

        $this->adminUpdateService->record(
            'Configuracion de tienda actualizada',
            $store->name,
            'tienda',
            route('admin.stores.edit', $store)
        );

        return redirect('/admin/store-settings')->with('success', 'Configuración de tienda actualizada.');
    }

    private function enforcePlanLimits(Store $store): void
    {
        if (! $store->allowsCategories()) {
            Product::where('store_id', $store->id)->update(['category' => null]);
        }

        if (! $store->allowsProductGallery()) {
            Product::where('store_id', $store->id)->update(['images' => null]);
        }

        if (Store::supportsShippingMethodsColumn() && ! $store->allowsShippingMethods()) {
            $data = ['shipping_methods' => []];

            if (Store::supportsLocalDeliveryColumns()) {
                $data['local_delivery_area'] = null;
                if (Store::supportsLocalDeliveryCityCodeColumn()) {
                    $data['local_delivery_city_code'] = null;
                }
                $data['local_delivery_cost'] = null;
                $data['outside_delivery_cost'] = null;
            }

            $store->forceFill($data)->save();
        }

        if (! $store->allowsFullCustomization()) {
            $store->forceFill([
                'brand_color' => null,
                'background_color' => null,
                'text_color' => Store::automaticTextColorFor(null),
                'font_family' => 'system',
                'responsive_product_columns' => 2,
                'show_hero_products_action' => false,
            ])->save();
        }

        if (Store::supportsCustomDomainColumns() && ! $store->allowsCustomDomain()) {
            $store->forceFill([
                'custom_domain' => null,
                'custom_domain_status' => Store::CUSTOM_DOMAIN_PENDING,
                'custom_domain_verified_at' => null,
            ])->save();
        }
    }

    private function storeCanUsePlan(Store $store, string $plan): bool
    {
        if ($plan === $store->plan) {
            return true;
        }

        $limit = match ($plan) {
            Store::PLAN_BASIC => Store::BASIC_PRODUCT_LIMIT,
            Store::PLAN_PRO => Store::PRO_PRODUCT_LIMIT,
            default => null,
        };

        return $limit === null || $store->products()->count() <= $limit;
    }
    
}
