<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Product;
use App\Models\Store;
use App\Models\StoreCategory;
use App\Services\ProductContentService;
use App\Services\ProductFileService;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function __construct(
        private ProductContentService $productContentService,
        private ProductFileService $productFileService,
    ) {
    }

    private function currentStore(): ?Store
    {
        $user = auth()->user();

        return $user?->store ?? $user?->stores()->first();
    }

    protected function countStoreVisit(Store $store): void
    {
        $user = auth()->user();

        if ($user && ($user->isAdmin() || (int) $user->id === (int) $store->user_id)) {
            return;
        }

        $store->increment('views_count');
    }

    public function index(?Store $store = null)
    {
        $this->authorize('viewAny', Product::class);

        $selectedStore = null;

        if (auth()->user()?->isAdmin()) {
            $selectedStore = $store?->exists ? $store : null;
            $stores = $selectedStore
                ? collect()
                : Store::withCount('products')->orderBy('name')->paginate(10);
            $products = $selectedStore
                ? Product::with('store')->where('store_id', $selectedStore->id)->latest()->get()
                : collect();

            return view('admin.products.index', compact('products', 'stores', 'selectedStore'));
        }

        $store = $this->currentStore();
        $products = $store
            ? Product::where('store_id', $store->id)->latest()->get()
            : collect();

        return view('admin.products.index', compact('products'));
    }

    public function create()
    {
        $this->authorize('create', Product::class);

        $store = $this->currentStore();
        $store?->ensureCategoryRecords();
        $stores = auth()->user()?->isAdmin()
            ? Store::orderBy('name')->get()
            : collect();
        $categoryOptions = auth()->user()?->isAdmin()
            ? StoreCategory::orderBy('name')->pluck('name')->unique()->values()->all()
            : ($store?->productCategoryOptions() ?? []);

        return view('admin.products.create', compact('store', 'stores', 'categoryOptions'));
    }

    public function store(ProductRequest $request)
    {
        $this->authorize('create', Product::class);

        $user = auth()->user();
        $store = $user?->isAdmin()
            ? Store::findOrFail($request->integer('store_id'))
            : $this->currentStore();

        if (! $store) {
            return back()->with('error', 'No tienes tienda creada.');
        }

        $this->productContentService->ensureStoreCategory($store, $request->category);

        $primaryImage = $this->productFileService->storeImage($request);
        $galleryImages = $this->productFileService->storeImages($request);

        if (! $primaryImage && ! empty($galleryImages)) {
            $primaryImage = array_shift($galleryImages);
        }

        Product::create(array_merge($request->baseData(), [
            'slug' => Product::uniqueSlugFor((int) $store->id, $request->name),
            'features' => $this->productContentService->cleanRichText($request->features),
            'sizes' => $this->productContentService->optionList($request->sizes),
            'colors' => $this->productContentService->optionList($request->colors),
            'image' => $primaryImage,
            'images' => $galleryImages,
            'user_id' => $user->isAdmin() ? $store->user_id : $user->id,
            'store_id' => $store->id,
        ]));

        return redirect('/admin/products')->with('success', 'Producto guardado.');
    }

    public function edit(Product $product)
    {
        $this->authorize('update', $product);

        $product->store?->ensureCategoryRecords();
        $stores = auth()->user()?->isAdmin()
            ? Store::orderBy('name')->get()
            : collect();
        $categoryOptions = auth()->user()?->isAdmin()
            ? StoreCategory::orderBy('name')->pluck('name')->unique()->values()->all()
            : ($product->store?->productCategoryOptions() ?? []);

        return view('admin.products.edit', compact('product', 'stores', 'categoryOptions'));
    }

    public function update(ProductRequest $request, Product $product)
    {
        $this->authorize('update', $product);

        $data = $request->baseData();
        $store = auth()->user()?->isAdmin()
            ? Store::findOrFail($request->integer('store_id'))
            : $product->store;

        if (auth()->user()?->isAdmin()) {
            $data['store_id'] = $store->id;
            $data['user_id'] = $store->user_id;
        }

        $data['slug'] = (! $product->slug || (int) $store?->id !== (int) $product->store_id)
            ? Product::uniqueSlugFor((int) $store->id, $request->name, $product->id)
            : $product->slug;
        $data['features'] = $this->productContentService->cleanRichText($request->features);
        $data['sizes'] = $this->productContentService->optionList($request->sizes);
        $data['colors'] = $this->productContentService->optionList($request->colors);

        if ($store) {
            $this->productContentService->ensureStoreCategory($store, $request->category);
        }

        $product->update($this->productFileService->replaceImage($product, $request, $data));

        return redirect('/admin/products')->with('success', 'Actualizado');
    }

    public function destroy(Product $product)
    {
        $this->authorize('delete', $product);

        $this->productFileService->deleteImage($product);

        $product->delete();

        return redirect()->back()->with('success', 'Eliminado');
    }

    public function storeBySlug($slug)
    {
        $store = Store::publiclyAvailable()
            ->where('slug', $slug)
            ->firstOrFail();

        $this->countStoreVisit($store);

        return view('store_shop', $this->storefrontPayload($store));
    }

    public function category($slug, string $category)
    {
        $store = Store::publiclyAvailable()
            ->where('slug', $slug)
            ->firstOrFail();

        $category = $store->categories()
            ->where('slug', $category)
            ->where('is_active', true)
            ->firstOrFail();

        $this->countStoreVisit($store);

        $products = Product::where('store_id', $store->id)
            ->where('category', $category->name)
            ->latest()
            ->paginate(7)
            ->withQueryString();

        return view('store_category', array_merge($this->storefrontNavigationPayload($store), [
            'category' => $category,
            'products' => $products,
        ]));
    }

    public function allProducts($slug)
    {
        $store = Store::publiclyAvailable()
            ->where('slug', $slug)
            ->firstOrFail();

        $this->countStoreVisit($store);

        $products = Product::where('store_id', $store->id)
            ->latest()
            ->paginate(24)
            ->withQueryString();

        return view('store_products', array_merge($this->storefrontNavigationPayload($store), [
            'products' => $products,
        ]));
    }

    public function show($slug, string $product)
    {
        $store = Store::publiclyAvailable()
            ->where('slug', $slug)
            ->firstOrFail();

        $product = Product::where('store_id', $store->id)
            ->where(function ($query) use ($product) {
                $query->where('slug', $product);

                if (ctype_digit($product)) {
                    $query->orWhere('id', (int) $product);
                }
            })
            ->firstOrFail();

        abort_unless((int) $product->store_id === (int) $store->id, 404);

        $this->countStoreVisit($store);

        $relatedProducts = Product::where('store_id', $store->id)
            ->whereKeyNot($product->id)
            ->latest()
            ->take(4)
            ->get();

        return view('store_product', array_merge($this->storefrontNavigationPayload($store), [
            'product' => $product,
            'relatedProducts' => $relatedProducts,
        ]));
    }

    private function storefrontPayload(Store $store): array
    {
        $activeCategories = $this->activeCategories($store);
        $categoryNames = $activeCategories->pluck('name')->all();
        $categoryProductCounts = empty($categoryNames)
            ? collect()
            : Product::where('store_id', $store->id)
                ->whereIn('category', $categoryNames)
                ->select('category', DB::raw('count(*) as total'))
                ->groupBy('category')
                ->pluck('total', 'category');

        $categorySections = $activeCategories
            ->filter(fn (StoreCategory $category) => (int) ($categoryProductCounts[$category->name] ?? 0) > 0)
            ->take(3)
            ->map(function (StoreCategory $category) use ($store, $categoryProductCounts) {
                return [
                    'category' => $category,
                    'products' => Product::where('store_id', $store->id)
                        ->where('category', $category->name)
                        ->latest()
                        ->take(4)
                        ->get(),
                    'total' => (int) ($categoryProductCounts[$category->name] ?? 0),
                ];
            })
            ->values();

        $visibleCategorySections = $categorySections;
        $otherProducts = Product::where('store_id', $store->id)
            ->where(function ($query) {
                $query->whereNull('category')->orWhere('category', '');
            })
            ->latest()
            ->take(4)
            ->get();

        $products = Product::where('store_id', $store->id)
            ->latest()
            ->paginate(7)
            ->withQueryString();
        $allProducts = Product::where('store_id', $store->id)
            ->latest()
            ->take(12)
            ->get();

        return compact(
            'store',
            'products',
            'allProducts',
            'activeCategories',
            'categorySections',
            'visibleCategorySections',
            'otherProducts'
        );
    }

    private function storefrontNavigationPayload(Store $store): array
    {
        return [
            'store' => $store,
            'activeCategories' => $this->activeCategories($store),
        ];
    }

    private function activeCategories(Store $store)
    {
        $store->ensureCategoryRecords();

        return $store->categories()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }
}
