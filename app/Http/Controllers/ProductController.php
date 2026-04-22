<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Product;
use App\Models\Store;
use App\Services\ProductContentService;
use App\Services\ProductFileService;

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

    public function index()
    {
        $this->authorize('viewAny', Product::class);

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
        $categoryOptions = $store?->productCategoryOptions() ?? [];

        return view('admin.products.create', compact('store', 'categoryOptions'));
    }

    public function store(ProductRequest $request)
    {
        $this->authorize('create', Product::class);

        $user = auth()->user();
        $store = $this->currentStore();

        if (! $store) {
            return back()->with('error', 'No tienes tienda creada.');
        }

        $this->productContentService->ensureStoreCategory($store, $request->category);

        Product::create(array_merge($request->baseData(), [
            'slug' => Product::uniqueSlugFor((int) $store->id, $request->name),
            'features' => $this->productContentService->cleanRichText($request->features),
            'sizes' => $this->productContentService->optionList($request->sizes),
            'colors' => $this->productContentService->optionList($request->colors),
            'image' => $this->productFileService->storeImage($request),
            'user_id' => $user->id,
            'store_id' => $store->id,
        ]));

        return redirect('/admin/products')->with('success', 'Producto guardado.');
    }

    public function edit(Product $product)
    {
        $this->authorize('update', $product);

        $product->store?->ensureCategoryRecords();
        $categoryOptions = $product->store?->productCategoryOptions() ?? [];

        return view('admin.products.edit', compact('product', 'categoryOptions'));
    }

    public function update(ProductRequest $request, Product $product)
    {
        $this->authorize('update', $product);

        $data = $request->baseData();
        $data['slug'] = $product->slug ?: Product::uniqueSlugFor((int) $product->store_id, $request->name, $product->id);
        $data['features'] = $this->productContentService->cleanRichText($request->features);
        $data['sizes'] = $this->productContentService->optionList($request->sizes);
        $data['colors'] = $this->productContentService->optionList($request->colors);

        if ($product->store) {
            $this->productContentService->ensureStoreCategory($product->store, $request->category);
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

        $allProducts = Product::where('store_id', $store->id)->latest()->get();
        $products = Product::where('store_id', $store->id)
            ->latest()
            ->paginate(7)
            ->withQueryString();

        return view('store_shop', compact('products', 'allProducts', 'store'));
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

        return view('store_product', compact('store', 'product', 'relatedProducts'));
    }
}
