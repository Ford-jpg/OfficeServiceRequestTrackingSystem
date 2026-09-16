<?php

namespace App\Policies;

use App\Models\Location;
use App\Models\User;

class LocationPolicy extends GenericRolePolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->is_active;
    }

    public function view(User $user, Location $location): bool
    {
        return (bool) $user->is_active;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Location $location): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Location $location): bool
    {
        return $user->isAdmin();
    }
}
