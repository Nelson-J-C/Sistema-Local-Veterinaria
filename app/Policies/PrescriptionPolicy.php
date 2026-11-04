<?php

namespace App\Policies;

use App\Models\Prescription;
use App\Models\User;

class PrescriptionPolicy
{
    /**
     * Determine whether the user can view prescriptions.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('consultations.view');
    }

    /**
     * Determine whether the user can view a prescription.
     */
    public function view(User $user, Prescription $prescription): bool
    {
        return $user->can('consultations.view');
    }

    /**
     * Prescriptions can be recorded while the consultation is still a draft.
     */
    public function create(User $user): bool
    {
        return $user->can('consultations.update');
    }

    /**
     * Prescriptions cannot be changed after the consultation is finalized.
     */
    public function update(User $user, Prescription $prescription): bool
    {
        return $user->can('consultations.update')
            && $prescription->consultation->isDraft();
    }

    /**
     * Prescriptions cannot be removed after the consultation is finalized.
     */
    public function delete(User $user, Prescription $prescription): bool
    {
        return $this->update($user, $prescription);
    }

    /**
     * Determine whether the user can restore a prescription.
     */
    public function restore(User $user, Prescription $prescription): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete a prescription.
     */
    public function forceDelete(User $user, Prescription $prescription): bool
    {
        return false;
    }
}
