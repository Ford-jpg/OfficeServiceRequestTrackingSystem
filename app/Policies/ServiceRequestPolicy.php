<?php

namespace App\Policies;

use App\Models\ServiceRequest;
use App\Models\User;

class ServiceRequestPolicy extends GenericRolePolicy
{
    public function view(User $user, ServiceRequest $serviceRequest): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        // Servicing department staff
        if ($this->isInUserDepartment($user, $serviceRequest)) {
            return true;
        }

        // Direct requester
        if ((int) $serviceRequest->requester_id === (int) $user->id) {
            return true;
        }

        // Requester's department/office colleagues
        $requesterDeptId = $serviceRequest->requester?->department_id;
        if ($user->department_id && $requesterDeptId && (int) $requesterDeptId === (int) $user->department_id) {
            return true;
        }

        return false;
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
