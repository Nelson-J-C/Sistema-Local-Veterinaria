<?php

namespace App\Policies;

use App\Models\ConsultationDiagnosis;
use App\Models\User;

class ConsultationDiagnosisPolicy
{
    /**
     * Determine whether the user can view diagnoses.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('consultations.view');
    }

    /**
     * Determine whether the user can view a diagnosis.
     */
    public function view(User $user, ConsultationDiagnosis $consultationDiagnosis): bool
    {
        return $user->can('consultations.view');
    }

    /**
     * Diagnoses can be recorded while the consultation is still a draft.
     */
    public function create(User $user): bool
    {
        return $user->can('consultations.update');
    }

    /**
     * Diagnoses cannot be changed after the consultation is finalized.
     */
    public function update(User $user, ConsultationDiagnosis $consultationDiagnosis): bool
    {
        return $user->can('consultations.update')
            && $consultationDiagnosis->consultation->isDraft();
    }

    /**
     * Diagnoses cannot be removed after the consultation is finalized.
     */
    public function delete(User $user, ConsultationDiagnosis $consultationDiagnosis): bool
    {
        return $this->update($user, $consultationDiagnosis);
    }

    /**
     * Determine whether the user can restore a diagnosis.
     */
    public function restore(User $user, ConsultationDiagnosis $consultationDiagnosis): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete a diagnosis.
     */
    public function forceDelete(User $user, ConsultationDiagnosis $consultationDiagnosis): bool
    {
        return false;
    }
}
