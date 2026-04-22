<?php

namespace App\Policies;

use App\Models\Store;
use App\Models\User;

class StorePolicy
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
        return $user->isAdmin();
    }

    public function update(User $user, Store $store): bool
    {
        return (int) $store->user_id === (int) $user->id;
    }
}
