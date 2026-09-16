<?php

namespace App\Models\Scopes;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Schema;

class DepartmentScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (! auth()->check()) {
            return;
        }

        $user = auth()->user();

        if ($user->isAdmin()) {
            return;
        }

        if ($model instanceof Department) {
            $builder->where($model->getQualifiedKeyName(), $user->department_id);
        } elseif ($model instanceof User) {
            $builder->where($model->qualifyColumn('department_id'), $user->department_id);
        } elseif (Schema::hasColumn($model->getTable(), 'department_id')) {
            $builder->where($model->qualifyColumn('department_id'), $user->department_id);
        }
    }
}
