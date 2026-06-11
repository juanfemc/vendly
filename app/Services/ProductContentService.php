<?php

namespace App\Services;

use App\Models\Store;
use App\Models\StoreCategory;
use App\Support\ProductText;

class ProductContentService
{
    public function ensureStoreCategory(Store $store, ?string $categoryName): void
    {
        $categoryName = trim((string) $categoryName);

        if ($categoryName === '') {
            return;
        }

        $store->categories()->firstOrCreate(
            ['name' => $categoryName],
            [
                'slug' => StoreCategory::uniqueSlugFor((int) $store->id, $categoryName),
                'is_active' => true,
            ]
        );
    }

    public function optionList(?string $value): array
    {
        return collect(explode(',', (string) $value))
            ->map(fn ($option) => trim($option))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function cleanRichText(?string $value): ?string
    {
        $value = ProductText::rich($value);

        if ($value === '') {
            return null;
        }

        return $value;
    }
}
