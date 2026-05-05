<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->role === 'store';
    }

    public function view(User $user, Order $order): bool
    {
        $store = $user->store ?? $user->stores()->first();

        return (bool) $store && (int) $order->store_id === (int) $store->id;
    }

    public function update(User $user, Order $order): bool
    {
        return $this->view($user, $order);
    }

    public function delete(User $user, Order $order): bool
    {
        return $this->view($user, $order);
    }
}
