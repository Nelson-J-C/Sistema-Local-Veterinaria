<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;

class PatientPolicy
{
    /**
     * Determine whether the user can list patients.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('patients.view');
    }

    /**
     * Determine whether the user can view a patient.
     */
    public function view(User $user, Patient $patient): bool
    {
        return $user->can('patients.view');
    }

    /**
     * Determine whether the user can create patients.
     */
    public function create(User $user): bool
    {
        return $user->can('patients.create');
    }

    /**
     * Determine whether the user can update a patient.
     */
    public function update(User $user, Patient $patient): bool
    {
        return $user->can('patients.update');
    }

    /**
     * Determine whether the user can soft delete a patient.
     */
    public function delete(User $user, Patient $patient): bool
    {
        return $user->can('patients.delete');
    }

    /**
     * Determine whether the user can restore a patient.
     */
    public function restore(User $user, Patient $patient): bool
    {
        return $user->can('patients.delete');
    }

    /**
     * Determine whether the user can permanently delete a patient.
     */
    public function forceDelete(User $user, Patient $patient): bool
    {
        return $user->can('patients.delete');
    }

    /**
     * Determine whether the user can soft delete patients in bulk.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('patients.delete');
    }

    /**
     * Determine whether the user can restore patients in bulk.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('patients.delete');
    }

    /**
     * Determine whether the user can permanently delete patients in bulk.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('patients.delete');
    }
}
