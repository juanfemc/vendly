<?php

namespace App\Services;

use App\Models\Store;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreSlugService
{
    public const RESERVED_SLUGS = [
        'admin',
        'api',
        'cart',
        'crear-tienda-gratis',
        'confirm-password',
        'dashboard',
        'email',
        'forgot-password',
        'login',
        'logout',
        'password',
        'profile',
        'categorias',
        'nosotros',
        'productos',
        'register',
        'reset-password',
        'storage',
        'verify-email',
    ];

    public function normalize(?string $value): string
    {
        return Str::slug((string) $value);
    }

    public function rules(?int $ignoreStoreId = null): array
    {
        return [
            'required',
            'string',
            'max:255',
            'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            Rule::notIn(self::RESERVED_SLUGS),
            Rule::unique('stores', 'slug')->ignore($ignoreStoreId),
        ];
    }

    public function uniqueFrom(string $value, ?int $ignoreStoreId = null): string
    {
        $base = $this->normalize($value);

        if ($base === '') {
            $base = 'tienda';
        }

        if (in_array($base, self::RESERVED_SLUGS, true)) {
            $base .= '-tienda';
        }

        $slug = $base;
        $suffix = 2;

        while ($this->exists($slug, $ignoreStoreId)) {
            $slug = $base . '-' . $suffix;
            $suffix++;
        }

        return $slug;
    }

    private function exists(string $slug, ?int $ignoreStoreId): bool
    {
        return Store::query()
            ->where('slug', $slug)
            ->when($ignoreStoreId, fn ($query) => $query->whereKeyNot($ignoreStoreId))
            ->exists();
    }
}
