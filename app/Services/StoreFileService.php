<?php

namespace App\Services;

use App\Models\Store;
use App\Models\StoreBanner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StoreFileService
{
    public function __construct(private PublicFileService $publicFileService)
    {
    }

    public function storeUploadedImages(Request $request): array
    {
        return [
            'cover_image' => $request->hasFile('cover_image')
                ? $request->file('cover_image')->store('stores', 'public')
                : null,
            'logo_image' => $request->hasFile('logo_image')
                ? $request->file('logo_image')->store('stores', 'public')
                : null,
        ];
    }

    public function replaceUploadedImages(Store $store, Request $request, array $data): array
    {
        if ($request->hasFile('cover_image')) {
            $this->deletePublicFile($store->cover_image);
            $data['cover_image'] = $request->file('cover_image')->store('stores', 'public');
        } elseif ($generatedCover = $this->safeGeneratedCoverPath($request->input('ai_generated_cover_path'))) {
            if ($generatedCover !== $store->cover_image) {
                $this->deletePublicFile($store->cover_image);
            }

            $data['cover_image'] = $generatedCover;
        }

        if ($request->hasFile('logo_image')) {
            $this->deletePublicFile($store->logo_image);
            $data['logo_image'] = $request->file('logo_image')->store('stores', 'public');
        }

        return $data;
    }

    public function deleteStoreFiles(Store $store): void
    {
        foreach ($store->products as $product) {
            $this->deletePublicFile($product->image);
            $this->publicFileService->deleteMany($product->images ?? []);
        }

        foreach ($store->banners as $banner) {
            $this->deleteBannerImageIfUnused($banner->image, $store->id);
        }

        $this->publicFileService->deleteMany($store->categories->pluck('image'));
        $this->deletePublicFile($store->cover_image);
        $this->deletePublicFile($store->logo_image);
    }

    private function deleteBannerImageIfUnused(?string $image, int $storeId): void
    {
        if (! $image) {
            return;
        }

        $isShared = StoreBanner::where('image', $image)
            ->where('store_id', '!=', $storeId)
            ->exists();

        if (! $isShared) {
            $this->deletePublicFile($image);
        }
    }

    private function deletePublicFile(?string $path): void
    {
        $this->publicFileService->delete($path);
    }

    private function safeGeneratedCoverPath(?string $path): ?string
    {
        $path = str_replace('\\', '/', trim((string) $path));

        if (! str_starts_with($path, 'stores/ai/') || str_contains($path, '..')) {
            return null;
        }

        return Storage::disk('public')->exists($path) ? $path : null;
    }
}
