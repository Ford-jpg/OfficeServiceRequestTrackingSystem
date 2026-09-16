<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy extends GenericRolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isServiceManager());
    }

    public function view(User $user, User $model): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->id === $model->id) {
            return true;
        }

        return $user->isServiceManager() && $this->isInUserDepartment($user, $model);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isServiceManager();
    }

    public function update(User $user, User $model): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->id === $model->id) {
            return true;
        }

        return $user->isServiceManager() && $this->isInUserDepartment($user, $model);
    }

    public function delete(User $user, User $model): bool
    {
        return $user->isAdmin();
    }
}
