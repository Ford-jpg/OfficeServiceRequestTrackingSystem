<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\User;

class DepartmentPolicy extends GenericRolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isServiceManager());
    }

    public function view(User $user, Department $department): bool
    {
        return $user->is_active && $this->isInUserDepartment($user, $department);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Department $department): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Department $department): bool
    {
        return $user->isAdmin();
    }
}
