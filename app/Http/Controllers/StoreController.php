<?php

namespace App\Http\Controllers;


use App\Http\Requests\StoreRequest;
use App\Http\Requests\StoreSettingsRequest;
use App\Models\Store;
use App\Models\User;
use App\Services\StoreFileService;

class StoreController extends Controller
{
    public function __construct(private StoreFileService $storeFileService)
    {
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

    public function store(StoreRequest $request)
    {
        $this->authorize('create', Store::class);

        Store::create(array_merge($request->storeData(), $this->storeFileService->storeUploadedImages($request), [
            'created_by_admin_id' => auth()->id(),
        ]));

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

        $store->update($this->storeFileService->replaceUploadedImages($store, $request, $request->storeData()));

        return redirect('/admin/stores')->with('success', 'Tienda actualizada.');
    }

    public function destroy(Store $store)
    {
        $this->authorize('update', $store);

        $this->storeFileService->deleteStoreFiles($store);

        $store->delete();

        return redirect('/admin/stores')->with('success', 'Tienda eliminada.');
    }

    public function settings()
    {
        $store = auth()->user()?->store ?? auth()->user()?->stores()->first();

        abort_if(! $store, 404);
        $this->authorize('update', $store);

        $businessTypeOptions = Store::businessTypeOptions();

        return view('admin.stores.settings', compact('store', 'businessTypeOptions'));
    }

    public function updateSettings(StoreSettingsRequest $request)
    {
        $store = auth()->user()?->store ?? auth()->user()?->stores()->first();

        abort_if(! $store, 404);
        $this->authorize('update', $store);

        $store->update($this->storeFileService->replaceUploadedImages($store, $request, $request->settingsData()));

        return redirect('/admin/store-settings')->with('success', 'Configuración de tienda actualizada.');
    }
    
}
