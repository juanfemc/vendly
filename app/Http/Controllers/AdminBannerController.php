<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\StoreBanner;
use App\Services\PublicFileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminBannerController extends Controller
{
    public function __construct(private PublicFileService $publicFileService)
    {
    }

    public function index(): View
    {
        $this->authorize('viewAny', StoreBanner::class);

        $banners = StoreBanner::with('store')
            ->orderBy('sort_order')
            ->latest()
            ->get()
            ->unique(fn (StoreBanner $banner) => $banner->applies_to_all && $banner->group_token
                ? 'group-' . $banner->group_token
                : 'single-' . $banner->id)
            ->values();

        return view('admin.banners.index', compact('banners'));
    }

    public function create(): View
    {
        $this->authorize('create', StoreBanner::class);

        $stores = Store::orderBy('name')->get();

        return view('admin.banners.create', compact('stores'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', StoreBanner::class);

        $request->validate([
            'store_id' => ['required'],
            'title' => ['nullable', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'image' => ['required', 'image', 'max:4096'],
            'link' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $storeIds = $request->store_id === 'all'
            ? Store::orderBy('id')->pluck('id')->all()
            : [(int) $request->store_id];
        $groupToken = $request->store_id === 'all' ? (string) Str::uuid() : null;

        if (empty($storeIds)) {
            return redirect('/admin/banners/create')->withErrors([
                'store_id' => 'No hay tiendas disponibles para asignar el banner.',
            ])->withInput();
        }

        if ($request->store_id !== 'all' && ! Store::whereKey($storeIds[0])->exists()) {
            return redirect('/admin/banners/create')->withErrors([
                'store_id' => 'La tienda seleccionada no existe.',
            ])->withInput();
        }

        $image = $request->file('image')->store('banners', 'public');

        foreach ($storeIds as $storeId) {
            StoreBanner::create([
                'store_id' => $storeId,
                'title' => $request->title,
                'subtitle' => $request->subtitle,
                'image' => $image,
                'link' => $request->link,
                'is_active' => $request->boolean('is_active', true),
                'applies_to_all' => $request->store_id === 'all',
                'group_token' => $groupToken,
                'sort_order' => $request->input('sort_order', 0),
            ]);
        }

        return redirect('/admin/banners')->with(
            'success',
            $request->store_id === 'all' ? 'Banner creado para todas las tiendas.' : 'Banner creado.'
        );
    }

    public function edit(StoreBanner $banner): View
    {
        $this->authorize('update', $banner);

        $stores = Store::orderBy('name')->get();

        return view('admin.banners.edit', compact('banner', 'stores'));
    }

    public function update(Request $request, StoreBanner $banner): RedirectResponse
    {
        $this->authorize('update', $banner);

        $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'image' => ['nullable', 'image', 'max:4096'],
            'link' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $data = [
            'title' => $request->input('title'),
            'subtitle' => $request->input('subtitle'),
            'link' => $request->input('link'),
            'sort_order' => $request->input('sort_order', 0),
            'is_active' => $request->boolean('is_active'),
        ];

        $oldImages = collect();
        $groupIds = collect();

        if ($request->hasFile('image')) {
            $groupBanners = $this->bannerGroupQuery($banner)->get(['id', 'image']);
            $groupIds = $groupBanners->pluck('id');
            $oldImages = $groupBanners
                ->pluck('image')
                ->filter()
                ->unique()
                ->values();

            $data['image'] = $request->file('image')->store('banners', 'public');
        }

        $this->bannerGroupQuery($banner)->update($data);

        $oldImages->each(function ($image) use ($groupIds) {
            $isShared = StoreBanner::where('image', $image)
                ->whereNotIn('id', $groupIds)
                ->exists();

            if (! $isShared) {
                $this->publicFileService->delete($image);
            }
        });

        return redirect('/admin/banners')->with('success', 'Banner actualizado.');
    }

    public function toggle(StoreBanner $banner): RedirectResponse
    {
        $this->authorize('update', $banner);

        $nextState = ! $banner->is_active;

        $this->bannerGroupQuery($banner)->update([
            'is_active' => $nextState,
        ]);

        return redirect('/admin/banners')->with(
            'success',
            $nextState ? 'Banner activado.' : 'Banner desactivado.'
        );
    }

    public function destroy(StoreBanner $banner): RedirectResponse
    {
        $this->authorize('delete', $banner);

        $banners = $this->bannerGroupQuery($banner)->get();

        foreach ($banners as $bannerItem) {
            $this->publicFileService->delete($bannerItem->image);
        }

        $this->bannerGroupQuery($banner)->delete();

        return redirect('/admin/banners')->with('success', 'Banner eliminado.');
    }

    protected function bannerGroupQuery(StoreBanner $banner)
    {
        if ($banner->applies_to_all && $banner->group_token) {
            return StoreBanner::where('group_token', $banner->group_token);
        }

        return StoreBanner::whereKey($banner->id);
    }
}
