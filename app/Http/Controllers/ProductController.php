<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Product;
use App\Models\Store;
use App\Models\StoreCategory;
use App\Services\AdminUpdateService;
use App\Services\ProductContentService;
use App\Services\ProductFileService;
use App\Services\StoreSubdomainService;
use App\Services\StorefrontUrlService;
use App\Services\StoreVisitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function __construct(
        private ProductContentService $productContentService,
        private ProductFileService $productFileService,
        private AdminUpdateService $adminUpdateService,
        private StoreVisitService $storeVisitService,
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

        $this->storeVisitService->record($store, request());
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

        return view('admin.products.index', compact('products', 'store'));
    }

    public function create()
    {
        $this->authorize('create', Product::class);

        $store = $this->currentStore();
        if ($store && ! auth()->user()?->isAdmin() && ! $store->canCreateMoreProducts()) {
            return redirect('/admin/products')
                ->with('error', 'El plan ' . $store->planLabel() . ' permite hasta ' . $store->productLimit() . ' productos.');
        }

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

        if (! $store->canCreateMoreProducts()) {
            return back()
                ->withInput()
                ->with('error', 'El plan ' . $store->planLabel() . ' permite hasta ' . $store->productLimit() . ' productos.');
        }

        if ($store->allowsCategories()) {
            $this->productContentService->ensureStoreCategory($store, $request->category);
        }

        $primaryImage = $this->productFileService->storeImage($request);
        $galleryImages = $store->allowsProductGallery()
            ? $this->productFileService->storeImages($request)
            : [];

        if (! $primaryImage && ! empty($galleryImages)) {
            $primaryImage = array_shift($galleryImages);
        }

        $productData = $this->productDataForStore($request, $store);

        $product = Product::create(array_merge($productData, [
            'slug' => Product::uniqueSlugFor((int) $store->id, $request->name),
            'features' => $this->productContentService->cleanRichText($request->features),
            'sizes' => $this->productContentService->optionList($request->sizes),
            'colors' => $this->productContentService->optionList($request->colors),
            'image' => $primaryImage,
            'images' => $galleryImages,
            'user_id' => $user->isAdmin() ? $store->user_id : $user->id,
            'store_id' => $store->id,
        ]));

        $this->adminUpdateService->record(
            'Producto creado',
            $product->name . ' en ' . $store->name,
            'producto',
            route('admin.products.edit', $product)
        );

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
        $productReviews = ($product->store?->allowsProductReviews() ?? false)
            ? $product->reviews()->latest()->get()
            : collect();

        return view('admin.products.edit', compact('product', 'stores', 'categoryOptions', 'productReviews'));
    }

    public function update(ProductRequest $request, Product $product)
    {
        $this->authorize('update', $product);

        $store = auth()->user()?->isAdmin()
            ? Store::findOrFail($request->integer('store_id'))
            : $product->store;

        if (! $this->storeCanAcceptProduct($store, $product)) {
            return back()
                ->withInput()
                ->with('error', 'El plan ' . $store->planLabel() . ' permite hasta ' . $store->productLimit() . ' productos.');
        }

        $data = $this->productDataForStore($request, $store);

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

        if ($store && $store->allowsCategories()) {
            $this->productContentService->ensureStoreCategory($store, $request->category);
        }

        $product->update($this->productFileService->replaceImage($product, $request, $data, $store->allowsProductGallery()));

        $this->adminUpdateService->record(
            'Producto actualizado',
            $product->name,
            'producto',
            route('admin.products.edit', $product)
        );

        return redirect('/admin/products')->with('success', 'Actualizado');
    }

    public function destroy(Product $product)
    {
        $this->authorize('delete', $product);

        $this->productFileService->deleteImage($product);

        $productName = $product->name;
        $product->delete();

        $this->adminUpdateService->record('Producto eliminado', $productName, 'producto');

        return redirect()->back()->with('success', 'Eliminado');
    }

    public function storeBySlug($slug)
    {
        return $this->storeHome($this->storeFromSlugOrFail($slug));
    }

    public function storeBySubdomain(Request $request, StoreSubdomainService $subdomains)
    {
        return $this->storeHome($this->storeFromSubdomainOrFail($request, $subdomains));
    }

    public function about($slug)
    {
        return $this->storeAbout($this->storeFromSlugOrFail($slug));
    }

    public function aboutBySubdomain(Request $request, StoreSubdomainService $subdomains)
    {
        return $this->storeAbout($this->storeFromSubdomainOrFail($request, $subdomains));
    }

    public function category($slug, string $category)
    {
        return $this->storeCategory($this->storeFromSlugOrFail($slug), $category);
    }

    public function categoryBySubdomain(Request $request, StoreSubdomainService $subdomains, string $category)
    {
        return $this->storeCategory($this->storeFromSubdomainOrFail($request, $subdomains), $category);
    }

    public function allProducts($slug)
    {
        return $this->storeProducts($this->storeFromSlugOrFail($slug));
    }

    public function allProductsBySubdomain(Request $request, StoreSubdomainService $subdomains)
    {
        return $this->storeProducts($this->storeFromSubdomainOrFail($request, $subdomains));
    }

    public function offers($slug)
    {
        return $this->storeOffers($this->storeFromSlugOrFail($slug));
    }

    public function offersBySubdomain(Request $request, StoreSubdomainService $subdomains)
    {
        return $this->storeOffers($this->storeFromSubdomainOrFail($request, $subdomains));
    }

    public function show($slug, string $product)
    {
        return $this->storeProduct($this->storeFromSlugOrFail($slug), $product);
    }

    public function showBySubdomain(Request $request, StoreSubdomainService $subdomains, string $product)
    {
        return $this->storeProduct($this->storeFromSubdomainOrFail($request, $subdomains), $product);
    }

    private function storeFromSlugOrFail(string $slug): Store
    {
        return Store::publiclyAvailable()
            ->where('slug', $slug)
            ->firstOrFail();
    }

    private function storeFromSubdomainOrFail(Request $request, StoreSubdomainService $subdomains): Store
    {
        $store = $subdomains->publicStoreFromRequest($request);

        abort_unless($store, 404);

        return $store;
    }

    private function storeHome(Store $store)
    {
        $isCatalogPartial = $store->isTechnologyStore() && request('partial') === 'catalogo';

        if (! $isCatalogPartial) {
            $this->countStoreVisit($store);
        }

        $payload = $this->storefrontPayload($store);

        if ($isCatalogPartial) {
            return view('storefront.partials.minimal-catalog', $payload);
        }

        return view('store_shop', $payload);
    }

    private function storeAbout(Store $store)
    {
        abort_unless($store->hasAboutContent(), 404);

        $this->countStoreVisit($store);

        return view('store_about', $this->storefrontNavigationPayload($store));
    }

    private function storeCategory(Store $store, string $categorySlug)
    {
        abort_unless($store->allowsCategories(), 404);

        $category = $store->categories()
            ->where('slug', $categorySlug)
            ->where('is_active', true)
            ->firstOrFail();

        $this->countStoreVisit($store);

        $productSearchEnabled = $store->hasProductSearch();
        $searchQuery = $productSearchEnabled ? $this->searchQuery() : '';

        $products = $this->publicProductsQuery($store, $searchQuery)
            ->where('category', $category->name)
            ->paginate(8)
            ->withQueryString();

        return view('store_category', array_merge($this->storefrontNavigationPayload($store), [
            'category' => $category,
            'products' => $products,
            'productSearchEnabled' => $productSearchEnabled,
            'searchQuery' => $searchQuery,
        ]));
    }

    private function storeProducts(Store $store)
    {
        $this->countStoreVisit($store);

        $productSearchEnabled = $store->hasProductSearch();
        $searchQuery = $productSearchEnabled ? $this->searchQuery() : '';

        $products = $this->publicProductsQuery($store, $searchQuery)
            ->paginate(24)
            ->withQueryString();

        return view('store_products', array_merge($this->storefrontNavigationPayload($store), [
            'products' => $products,
            'productSearchEnabled' => $productSearchEnabled,
            'searchQuery' => $searchQuery,
        ]));
    }

    private function storeOffers(Store $store)
    {
        abort_unless($store->hasOfferProducts(), 404);

        $this->countStoreVisit($store);

        $productSearchEnabled = $store->hasProductSearch();
        $searchQuery = $productSearchEnabled ? $this->searchQuery() : '';

        $products = $this->publicProductsQuery($store, $searchQuery)
            ->where('has_offer', true)
            ->paginate(24)
            ->withQueryString();

        return view('store_offers', array_merge($this->storefrontNavigationPayload($store), [
            'products' => $products,
            'productSearchEnabled' => $productSearchEnabled,
            'searchQuery' => $searchQuery,
        ]));
    }

    private function storeProduct(Store $store, string $productKey)
    {
        $product = $this->publicProductFromKey($store, $productKey);

        $this->countStoreVisit($store);

        $relatedProducts = Product::where('store_id', $store->id)
            ->withReviewStats()
            ->whereKeyNot($product->id)
            ->latest()
            ->take(4)
            ->get();

        return view('store_product', array_merge($this->storefrontNavigationPayload($store), [
            'product' => $product,
            'relatedProducts' => $relatedProducts,
        ]));
    }

    private function publicProductFromKey(Store $store, string $productKey): Product
    {
        return Product::where('store_id', $store->id)
            ->withReviewStats()
            ->where(function ($query) use ($productKey) {
                $query->where('slug', $productKey);

                if (ctype_digit($productKey)) {
                    $query->orWhere('id', (int) $productKey);
                }
            })
            ->firstOrFail();
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
                        ->withReviewStats()
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
            ->withReviewStats()
            ->where(function ($query) {
                $query->whereNull('category')->orWhere('category', '');
            })
            ->latest()
            ->take(4)
            ->get();

        $homeProductPageSize = $store->isTechnologyStore() ? 6 : 7;
        $customBadgeFilters = $this->customBadgeFilters($store);
        $selectedHomeCategory = $store->isTechnologyStore()
            ? $activeCategories->firstWhere('slug', request('categoria'))
            : null;
        $selectedHomeBadge = $store->isTechnologyStore()
            ? $customBadgeFilters->first(fn ($badge) => $badge === trim((string) request('etiqueta')))
            : null;

        $products = $this->publicProductsQuery($store)
            ->when($selectedHomeCategory, fn ($query) => $query->where('category', $selectedHomeCategory->name))
            ->when($selectedHomeBadge, fn ($query) => $query->whereJsonContains('custom_badges', $selectedHomeBadge))
            ->paginate($homeProductPageSize)
            ->withQueryString();
        $storeProductsTotal = Product::where('store_id', $store->id)->count();
        $allProducts = Product::where('store_id', $store->id)
            ->withReviewStats()
            ->latest()
            ->take(12)
            ->get();
        $productSearchEnabled = $store->hasProductSearch();
        $storefrontUrls = app(StorefrontUrlService::class);

        return compact(
            'store',
            'storefrontUrls',
            'products',
            'allProducts',
            'activeCategories',
            'categorySections',
            'categoryProductCounts',
            'visibleCategorySections',
            'otherProducts',
            'productSearchEnabled',
            'selectedHomeCategory',
            'selectedHomeBadge',
            'customBadgeFilters',
            'storeProductsTotal'
        );
    }

    private function storefrontNavigationPayload(Store $store): array
    {
        return [
            'store' => $store,
            'storefrontUrls' => app(StorefrontUrlService::class),
            'activeCategories' => $this->activeCategories($store),
            'customBadgeFilters' => $this->customBadgeFilters($store),
            'showAboutSection' => $store->hasAboutContent(),
            'productSearchEnabled' => $store->hasProductSearch(),
        ];
    }

    private function customBadgeFilters(Store $store)
    {
        if (! $store->allowsCustomProductBadges() || ! Product::supportsCustomBadgesColumn()) {
            return collect();
        }

        return Product::where('store_id', $store->id)
            ->whereNotNull('custom_badges')
            ->get(['custom_badges'])
            ->flatMap(fn (Product $product) => $product->customBadges())
            ->map(fn ($badge) => trim((string) $badge))
            ->filter()
            ->unique()
            ->take(8)
            ->values();
    }

    private function searchQuery(): string
    {
        return trim((string) request('q', ''));
    }

    private function applyProductSearch($query, string $searchQuery)
    {
        $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $searchQuery) . '%';

        return $query->where(function ($query) use ($like) {
            $query->where('name', 'like', $like);
        });
    }

    private function publicProductsQuery(Store $store, string $searchQuery = '')
    {
        return Product::where('store_id', $store->id)
            ->withReviewStats()
            ->when($searchQuery !== '', fn ($query) => $this->applyProductSearch($query, $searchQuery))
            ->latest();
    }

    private function productDataForStore(ProductRequest $request, Store $store): array
    {
        $data = $request->baseData();

        if (! Product::supportsOfferColumn()) {
            unset($data['has_offer'], $data['offer_original_price']);
        } elseif (! Product::supportsOfferPricingColumn()) {
            unset($data['offer_original_price']);
        }

        if (! $store->allowsOfferBadges()) {
            $data['has_offer'] = false;
            $data['offer_original_price'] = null;
        }

        if (! Product::supportsCustomBadgesColumn()) {
            unset($data['custom_badges']);
        } elseif (! $store->allowsCustomProductBadges()) {
            $data['custom_badges'] = [];
        }

        if (! $store->allowsCategories()) {
            $data['category'] = null;
        }

        if (! Product::supportsInventoryColumns()) {
            unset($data['stock_quantity'], $data['is_sold_out']);

            return $data;
        }

        if ($store->isReservationStore()) {
            $data['stock_quantity'] = null;
            $data['is_sold_out'] = false;
        }

        return $data;
    }

    private function storeCanAcceptProduct(Store $store, Product $product): bool
    {
        if ((int) $product->store_id === (int) $store->id) {
            return true;
        }

        $limit = $store->productLimit();

        if ($limit === null) {
            return true;
        }

        $count = $store->products()
            ->whereKeyNot($product->id)
            ->count();

        return $count < $limit;
    }

    private function activeCategories(Store $store)
    {
        if (! $store->allowsCategories()) {
            return collect();
        }

        $store->ensureCategoryRecords();

        return $store->categories()
            ->where('is_active', true)
            ->orderedForDisplay()
            ->get();
    }
}
