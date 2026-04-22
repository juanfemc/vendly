<?php

namespace App\Policies;

use App\Models\StoreBanner;
use App\Models\User;

class StoreBannerPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, StoreBanner $storeBanner): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, StoreBanner $storeBanner): bool
    {
        return $user->isAdmin();
    }
}
