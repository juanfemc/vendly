<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductFileService
{
    public function __construct(private PublicFileService $publicFileService)
    {
    }

    public function storeImage(Request $request): ?string
    {
        if ($request->hasFile('image')) {
            return $request->file('image')->store('products', 'public');
        }

        return $this->safeGeneratedImagePath($request->input('ai_generated_image_path'));
    }

    public function storeImages(Request $request): array
    {
        if (! $request->hasFile('images')) {
            return [];
        }

        return collect($request->file('images'))
            ->filter()
            ->map(fn ($image) => $image->store('products', 'public'))
            ->values()
            ->all();
    }

    public function replaceImage(Product $product, Request $request, array $data, bool $allowGallery = true): array
    {
        $existingImages = collect($product->images ?? []);
        $removeImages = collect($request->input('remove_images', []))
            ->filter()
            ->intersect($existingImages)
            ->values();

        if ($allowGallery && $removeImages->isNotEmpty()) {
            $this->publicFileService->deleteMany($removeImages);
            $existingImages = $existingImages
                ->reject(fn ($image) => $removeImages->contains($image))
                ->values();
            $data['images'] = $existingImages->all();
        }

        if ($request->hasFile('image')) {
            $this->deletePrimaryImage($product);
            $data['image'] = $request->file('image')->store('products', 'public');
        } elseif ($generatedImage = $this->safeGeneratedImagePath($request->input('ai_generated_image_path'))) {
            if ($generatedImage !== $product->image) {
                $this->deletePrimaryImage($product);
            }

            $data['image'] = $generatedImage;
        }

        if ($allowGallery && $request->hasFile('images')) {
            $newImages = $this->storeImages($request);

            if (! $product->image && ! ($data['image'] ?? null) && ! empty($newImages)) {
                $data['image'] = array_shift($newImages);
            }

            $data['images'] = $existingImages
                ->merge($newImages)
                ->values()
                ->all();
        }

        return $data;
    }

    public function deleteImage(Product $product): void
    {
        $this->deletePrimaryImage($product);

        $this->publicFileService->deleteMany($product->images ?? []);
    }

    private function deletePrimaryImage(Product $product): void
    {
        $this->publicFileService->delete($product->image);
    }

    private function safeGeneratedImagePath(?string $path): ?string
    {
        $path = str_replace('\\', '/', trim((string) $path));

        if (! str_starts_with($path, 'products/ai/') || str_contains($path, '..')) {
            return null;
        }

        return Storage::disk('public')->exists($path) ? $path : null;
    }
}
