<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\User;

abstract class GenericRolePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    public function isInUserDepartment(User $user, mixed $model = null): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (! $user->department_id) {
            return false;
        }

        if (! $model) {
            return true;
        }

        if ($model instanceof Department) {
            return (int) $model->id === (int) $user->department_id;
        }

        if ($model instanceof User) {
            return (int) $model->department_id === (int) $user->department_id;
        }

        if (is_object($model) && isset($model->department_id)) {
            return (int) $model->department_id === (int) $user->department_id;
        }

        return true;
    }

    public function viewAny(User $user): bool
    {
        return (bool) $user->is_active;
    }

    public function create(User $user): bool
    {
        return (bool) $user->is_active;
    }
}
