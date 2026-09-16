<?php

namespace App\Policies;

use App\Models\ServiceRequest;
use App\Models\User;

class ServiceRequestPolicy extends GenericRolePolicy
{
    public function view(User $user, ServiceRequest $serviceRequest): bool
    {
        return $user->is_active && $this->isInUserDepartment($user, $serviceRequest);
    }

    public function update(User $user, ServiceRequest $serviceRequest): bool
    {
        return $user->is_active
            && $user->canUpdateStatus()
            && $this->isInUserDepartment($user, $serviceRequest);
    }

    public function updateStatus(User $user, ServiceRequest $serviceRequest): bool
    {
        return $user->is_active
            && $user->canUpdateStatus()
            && $this->isInUserDepartment($user, $serviceRequest);
    }

    public function delete(User $user, ServiceRequest $serviceRequest): bool
    {
        return $user->isAdmin()
            || ($user->isServiceManager() && $this->isInUserDepartment($user, $serviceRequest));
    }
}
