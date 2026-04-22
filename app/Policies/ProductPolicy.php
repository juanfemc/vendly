<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->role === 'store';
    }

    public function create(User $user): bool
    {
        return $user->role === 'store' && (bool) ($user->store ?? $user->stores()->first());
    }

    public function update(User $user, Product $product): bool
    {
        $store = $user->store ?? $user->stores()->first();

        return (bool) $store && (int) $product->store_id === (int) $store->id;
    }

    public function delete(User $user, Product $product): bool
    {
        return $this->update($user, $product);
    }
}
