<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vaccination;

class VaccinationPolicy
{
    /**
     * Determine whether the user can list vaccinations.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('vaccinations.view');
    }

    /**
     * Determine whether the user can view a vaccination.
     */
    public function view(User $user, Vaccination $vaccination): bool
    {
        return $user->can('vaccinations.view');
    }

    /**
     * Determine whether the user can record a vaccination.
     */
    public function create(User $user): bool
    {
        return $user->can('vaccinations.create');
    }

    /**
     * Determine whether the user can correct a vaccination record.
     */
    public function update(User $user, Vaccination $vaccination): bool
    {
        return $user->can('vaccinations.update');
    }

    /**
     * Vaccination records are part of the clinical history and are not deleted.
     */
    public function delete(User $user, Vaccination $vaccination): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore a vaccination.
     */
    public function restore(User $user, Vaccination $vaccination): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete a vaccination.
     */
    public function forceDelete(User $user, Vaccination $vaccination): bool
    {
        return false;
    }
}
