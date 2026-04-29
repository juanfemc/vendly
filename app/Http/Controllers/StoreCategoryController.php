<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Store;
use App\Models\StoreCategory;
use App\Services\PublicFileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StoreCategoryController extends Controller
{
    public function __construct(private PublicFileService $publicFileService)
    {
    }

    public function index(?Store $store = null): View
    {
        $selectedStore = null;

        if (auth()->user()?->isAdmin()) {
            $stores = Store::withCount('categories')->orderBy('name')->paginate(10);
            $selectedStore = $store?->exists ? $store : null;

            if (! $selectedStore) {
                return view('admin.categories.index', [
                    'store' => null,
                    'stores' => $stores,
                    'selectedStore' => null,
                    'categories' => collect(),
                ]);
            }
        }

        $store = $selectedStore ?: $this->currentStore();
        $categories = $store->categories()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.categories.index', compact('store', 'categories', 'selectedStore'));
    }

    public function store(Request $request): RedirectResponse
    {
        $store = $this->storeForRequest($request);
        $request->merge([
            'slug' => Str::slug($request->input('slug') ?: $request->input('name')),
        ]);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('store_categories', 'name')->where(
                    fn ($query) => $query->where('store_id', $store->id)
                ),
            ],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('store_categories', 'slug')->where(
                    fn ($query) => $query->where('store_id', $store->id)
                ),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'image' => ['nullable', 'image', 'max:4096'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $store->categories()->create([
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?: StoreCategory::uniqueSlugFor((int) $store->id, $validated['name']),
            'description' => $validated['description'] ?? null,
            'image' => $request->hasFile('image') ? $request->file('image')->store('categories', 'public') : null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return $this->redirectToCategories($store)->with('success', 'Categoria creada.');
    }

    public function edit(StoreCategory $category): View
    {
        $store = auth()->user()?->isAdmin()
            ? $category->store
            : $this->currentStore();
        abort_unless($store && (int) $category->store_id === (int) $store->id, 404);

        return view('admin.categories.edit', compact('store', 'category'));
    }

    public function update(Request $request, StoreCategory $category): RedirectResponse
    {
        $store = auth()->user()?->isAdmin()
            ? $category->store
            : $this->currentStore();
        abort_unless($store && (int) $category->store_id === (int) $store->id, 404);
        $request->merge([
            'slug' => Str::slug($request->input('slug') ?: $request->input('name')),
        ]);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('store_categories', 'name')
                    ->where(fn ($query) => $query->where('store_id', $store->id))
                    ->ignore($category->id),
            ],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('store_categories', 'slug')
                    ->where(fn ($query) => $query->where('store_id', $store->id))
                    ->ignore($category->id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'image' => ['nullable', 'image', 'max:4096'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $oldName = $category->name;
        $newName = $validated['name'];
        $image = $category->image;

        if ($request->hasFile('image')) {
            $this->deleteCategoryImage($category->image);
            $image = $request->file('image')->store('categories', 'public');
        }

        $category->update([
            'name' => $newName,
            'slug' => $validated['slug'] ?: StoreCategory::uniqueSlugFor((int) $store->id, $newName, $category->id),
            'description' => $validated['description'] ?? null,
            'image' => $image,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        if ($oldName !== $newName) {
            Product::where('store_id', $store->id)
                ->where('category', $oldName)
                ->update(['category' => $newName]);
        }

        return $this->redirectToCategories($store)->with('success', 'Categoria actualizada.');
    }

    public function destroy(StoreCategory $category): RedirectResponse
    {
        $store = auth()->user()?->isAdmin()
            ? $category->store
            : $this->currentStore();
        abort_unless($store && (int) $category->store_id === (int) $store->id, 404);

        Product::where('store_id', $store->id)
            ->where('category', $category->name)
            ->update(['category' => null]);

        $this->deleteCategoryImage($category->image);

        $category->delete();

        return $this->redirectToCategories($store)->with('success', 'Categoria eliminada.');
    }

    protected function currentStore(): Store
    {
        $store = auth()->user()?->store ?? auth()->user()?->stores()->first();

        abort_if(! $store, 404);

        $store->ensureCategoryRecords();

        return $store;
    }

    private function storeForRequest(Request $request): Store
    {
        if (auth()->user()?->isAdmin()) {
            $store = Store::findOrFail($request->integer('store_id'));
            $store->ensureCategoryRecords();

            return $store;
        }

        return $this->currentStore();
    }

    private function redirectToCategories(Store $store): RedirectResponse
    {
        if (auth()->user()?->isAdmin()) {
            return redirect()->route('admin.stores.categories.index', $store);
        }

        return redirect()->route('admin.categories.index');
    }

    private function deleteCategoryImage(?string $path): void
    {
        $this->publicFileService->delete($path);
    }
}
