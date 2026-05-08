<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreSlugService
{
    public const RESERVED_SLUGS = [
        'admin',
        'api',
        'cart',
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
}
