<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vaccine;

class VaccinePolicy
{
    /**
     * Determine whether the user can list the vaccine catalog.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('vaccinations.view');
    }

    /**
     * Determine whether the user can view a vaccine.
     */
    public function view(User $user, Vaccine $vaccine): bool
    {
        return $user->can('vaccinations.view');
    }

    /**
     * Determine whether the user can add vaccines to the catalog.
     */
    public function create(User $user): bool
    {
        return $user->can('vaccinations.create');
    }

    /**
     * Determine whether the user can update a vaccine.
     */
    public function update(User $user, Vaccine $vaccine): bool
    {
        return $user->can('vaccinations.update');
    }

    /**
     * Vaccines already used in a vaccination record are kept for traceability.
     */
    public function delete(User $user, Vaccine $vaccine): bool
    {
        return $user->can('vaccinations.update') && ! $vaccine->vaccinations()->exists();
    }

    /**
     * Determine whether the user can restore a vaccine.
     */
    public function restore(User $user, Vaccine $vaccine): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete a vaccine.
     */
    public function forceDelete(User $user, Vaccine $vaccine): bool
    {
        return false;
    }
}
