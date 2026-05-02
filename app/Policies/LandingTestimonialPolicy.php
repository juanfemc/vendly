<?php

namespace App\Policies;

use App\Models\LandingTestimonial;
use App\Models\User;

class LandingTestimonialPolicy
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

    public function update(User $user, LandingTestimonial $landingTestimonial): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, LandingTestimonial $landingTestimonial): bool
    {
        return $user->isAdmin();
    }
}
