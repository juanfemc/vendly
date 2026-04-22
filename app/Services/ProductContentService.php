<?php

namespace App\Services;

use App\Models\Store;
use Illuminate\Support\Str;

class ProductContentService
{
    public function ensureStoreCategory(Store $store, ?string $categoryName): void
    {
        $categoryName = trim((string) $categoryName);

        if ($categoryName === '') {
            return;
        }

        $store->categories()->firstOrCreate([
            'name' => $categoryName,
        ]);
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
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $allowedTags = '<p><br><strong><b><em><i><u><ul><ol><li><h3><h4>';

        return Str::of(strip_tags($value, $allowedTags))
            ->replaceMatches('/<([a-z0-9]+)(?:\s[^>]*)?>/i', '<$1>')
            ->toString();
    }
}
