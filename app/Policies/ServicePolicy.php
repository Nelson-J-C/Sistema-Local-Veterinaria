<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;

class ServicePolicy
{
    /**
     * Determine whether the user can list services.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('services.view');
    }

    /**
     * Determine whether the user can view a service.
     */
    public function view(User $user, Service $service): bool
    {
        return $user->can('services.view');
    }

    /**
     * Determine whether the user can create services.
     */
    public function create(User $user): bool
    {
        return $user->can('services.create');
    }

    /**
     * Determine whether the user can update a service.
     */
    public function update(User $user, Service $service): bool
    {
        return $user->can('services.update');
    }

    /**
     * Services used in receipts are deactivated instead of deleted.
     */
    public function delete(User $user, Service $service): bool
    {
        return $user->can('services.delete')
            && ! $service->invoiceItems()->exists();
    }

    /**
     * Determine whether the user can restore a service.
     */
    public function restore(User $user, Service $service): bool
    {
        return $user->can('services.update');
    }

    /**
     * Services are permanently kept once used.
     */
    public function forceDelete(User $user, Service $service): bool
    {
        return false;
    }
}
