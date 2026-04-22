<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Store;
use App\Models\StoreCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StoreCategoryController extends Controller
{
    public function index(): View
    {
        $store = $this->currentStore();
        $categories = $store->categories()->orderBy('name')->get();

        return view('admin.categories.index', compact('store', 'categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $store = $this->currentStore();

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('store_categories', 'name')->where(
                    fn ($query) => $query->where('store_id', $store->id)
                ),
            ],
        ]);

        $store->categories()->create([
            'name' => $validated['name'],
        ]);

        return redirect('/admin/categories')->with('success', 'Categoria creada.');
    }

    public function edit(StoreCategory $category): View
    {
        $store = $this->currentStore();
        abort_unless((int) $category->store_id === (int) $store->id, 404);

        return view('admin.categories.edit', compact('store', 'category'));
    }

    public function update(Request $request, StoreCategory $category): RedirectResponse
    {
        $store = $this->currentStore();
        abort_unless((int) $category->store_id === (int) $store->id, 404);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('store_categories', 'name')
                    ->where(fn ($query) => $query->where('store_id', $store->id))
                    ->ignore($category->id),
            ],
        ]);

        $oldName = $category->name;
        $newName = $validated['name'];

        $category->update([
            'name' => $newName,
        ]);

        if ($oldName !== $newName) {
            Product::where('store_id', $store->id)
                ->where('category', $oldName)
                ->update(['category' => $newName]);
        }

        return redirect('/admin/categories')->with('success', 'Categoria actualizada.');
    }

    public function destroy(StoreCategory $category): RedirectResponse
    {
        $store = $this->currentStore();
        abort_unless((int) $category->store_id === (int) $store->id, 404);

        Product::where('store_id', $store->id)
            ->where('category', $category->name)
            ->update(['category' => null]);

        $category->delete();

        return redirect('/admin/categories')->with('success', 'Categoria eliminada.');
    }

    protected function currentStore(): Store
    {
        $store = auth()->user()?->store ?? auth()->user()?->stores()->first();

        abort_if(! $store, 404);

        $store->ensureCategoryRecords();

        return $store;
    }
}
