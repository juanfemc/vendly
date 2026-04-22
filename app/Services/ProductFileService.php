<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductFileService
{
    public function storeImage(Request $request): ?string
    {
        return $request->hasFile('image')
            ? $request->file('image')->store('products', 'public')
            : null;
    }

    public function replaceImage(Product $product, Request $request, array $data): array
    {
        if ($request->hasFile('image')) {
            $this->deleteImage($product);
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        return $data;
    }

    public function deleteImage(Product $product): void
    {
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }
    }
}
