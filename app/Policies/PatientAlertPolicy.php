<?php

namespace App\Policies;

use App\Models\PatientAlert;
use App\Models\User;

class PatientAlertPolicy
{
    /**
     * Alerts are managed as part of the patient record.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('patients.view');
    }

    /**
     * Determine whether the user can view an alert.
     */
    public function view(User $user, PatientAlert $alert): bool
    {
        return $user->can('patients.view');
    }

    /**
     * Determine whether the user can create alerts.
     */
    public function create(User $user): bool
    {
        return $user->can('patients.update');
    }

    /**
     * Determine whether the user can update an alert.
     */
    public function update(User $user, PatientAlert $alert): bool
    {
        return $user->can('patients.update');
    }

    /**
     * Determine whether the user can delete an alert.
     */
    public function delete(User $user, PatientAlert $alert): bool
    {
        return $user->can('patients.update');
    }
}
