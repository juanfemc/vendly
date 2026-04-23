<?php

namespace App\Services;

use App\Models\Store;
use App\Models\StoreBanner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StoreFileService
{
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
        }

        foreach ($store->banners as $banner) {
            $this->deleteBannerImageIfUnused($banner->image, $store->id);
        }

        foreach ($store->categories as $category) {
            $this->deletePublicFile($category->image);
        }

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

    public function deletePublicFile(?string $path): void
    {
        if (! $path) {
            return;
        }

        $disk = Storage::disk('public');
        $disk->delete($path);

        if ($disk->exists($path)) {
            @unlink($disk->path($path));
        }
    }
}
