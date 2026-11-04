<?php

namespace App\Policies;

use App\Models\PatientMeasurement;
use App\Models\User;

class PatientMeasurementPolicy
{
    /**
     * Determine whether the user can view measurements.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('consultations.view');
    }

    /**
     * Determine whether the user can view a measurement.
     */
    public function view(User $user, PatientMeasurement $patientMeasurement): bool
    {
        return $user->can('consultations.view');
    }

    /**
     * Measurements can be recorded while the consultation is still a draft.
     */
    public function create(User $user): bool
    {
        return $user->can('consultations.update');
    }

    /**
     * Measurements cannot be changed after the consultation is finalized.
     */
    public function update(User $user, PatientMeasurement $patientMeasurement): bool
    {
        return $user->can('consultations.update')
            && ($patientMeasurement->consultation?->isDraft() ?? true);
    }

    /**
     * Measurements cannot be removed after the consultation is finalized.
     */
    public function delete(User $user, PatientMeasurement $patientMeasurement): bool
    {
        return $this->update($user, $patientMeasurement);
    }

    /**
     * Determine whether the user can restore a measurement.
     */
    public function restore(User $user, PatientMeasurement $patientMeasurement): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete a measurement.
     */
    public function forceDelete(User $user, PatientMeasurement $patientMeasurement): bool
    {
        return false;
    }
}
