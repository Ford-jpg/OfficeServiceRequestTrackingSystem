<?php

namespace App\Policies;

use App\Models\ServiceCategory;
use App\Models\User;

class ServiceCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, ServiceCategory $serviceCategory): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, ServiceCategory $serviceCategory): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, ServiceCategory $serviceCategory): bool
    {
        return $user->isAdmin();
    }
}
