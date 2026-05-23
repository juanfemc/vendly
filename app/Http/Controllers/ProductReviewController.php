<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Http\Request;

class ProductReviewController extends Controller
{
    public function store(Request $request, Product $product)
    {
        $product->loadMissing('store');
        $store = $product->store;

        abort_unless($store?->allowsProductReviews() && $store->isAvailable(), 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        ProductReview::create([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'name' => trim($data['name']),
            'rating' => (int) $data['rating'],
            'comment' => trim((string) ($data['comment'] ?? '')) ?: null,
            'is_approved' => false,
        ]);

        return back()->with('review_success', 'Gracias, tu resena quedo pendiente de aprobacion.');
    }

    public function approve(ProductReview $review)
    {
        $this->authorizeReview($review);

        $review->update(['is_approved' => true]);

        return back()->with('success', 'Resena aprobada.');
    }

    public function destroy(ProductReview $review)
    {
        $this->authorizeReview($review);

        $review->delete();

        return back()->with('success', 'Resena eliminada.');
    }

    private function authorizeReview(ProductReview $review): void
    {
        $review->loadMissing('product.store');

        abort_unless($review->product?->store?->allowsProductReviews(), 404);

        $this->authorize('update', $review->product);
    }
}
