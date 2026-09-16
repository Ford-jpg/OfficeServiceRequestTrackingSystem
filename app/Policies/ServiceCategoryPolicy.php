<?php

namespace App\Policies;

use App\Models\ServiceCategory;
use App\Models\User;

class ServiceCategoryPolicy extends GenericRolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && in_array($user->role, ['admin', 'service_manager', 'technician'], true);
    }

    public function view(User $user, ServiceCategory $category): bool
    {
        return $user->is_active && $this->isInUserDepartment($user, $category);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isServiceManager();
    }

    public function update(User $user, ServiceCategory $category): bool
    {
        return $user->isAdmin()
            || ($user->isServiceManager() && $this->isInUserDepartment($user, $category));
    }

    public function delete(User $user, ServiceCategory $category): bool
    {
        return $user->isAdmin()
            || ($user->isServiceManager() && $this->isInUserDepartment($user, $category));
    }
}
