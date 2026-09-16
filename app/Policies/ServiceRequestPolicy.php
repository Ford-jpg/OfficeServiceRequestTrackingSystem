<?php

namespace App\Policies;

use App\Models\ServiceRequest;
use App\Models\User;

class ServiceRequestPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return (bool) $user->is_active;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ServiceRequest $serviceRequest): bool
    {
        if (! $user->is_active) {
            return false;
        }

        // Staff can view all requests
        if ($user->canUpdateStatus()) {
            return true;
        }

        // Requester can view their own requests
        return $serviceRequest->requester_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return (bool) $user->is_active;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ServiceRequest $serviceRequest): bool
    {
        // Only authorized personnel can update tickets
        return $user->is_active && $user->canUpdateStatus();
    }

    /**
     * Determine whether the user can update the status specifically.
     */
    public function updateStatus(User $user, ServiceRequest $serviceRequest): bool
    {
        return $user->is_active && $user->canUpdateStatus();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ServiceRequest $serviceRequest): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ServiceRequest $serviceRequest): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ServiceRequest $serviceRequest): bool
    {
        return $user->isAdmin();
    }
}
