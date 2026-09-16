<?php

namespace App\Policies;

use App\Models\ServiceRequest;
use App\Models\User;

class ServiceRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ServiceRequest $serviceRequest): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, ServiceRequest $serviceRequest): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, ServiceRequest $serviceRequest): bool
    {
        return $user->isAdmin();
    }

    public function updateStatus(User $user, ServiceRequest $serviceRequest): bool
    {
        return $user->canUpdateStatus();
    }
}
