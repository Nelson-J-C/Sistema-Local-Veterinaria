<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    /**
     * Determine whether the user can list appointments.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('appointments.view');
    }

    /**
     * Determine whether the user can view an appointment.
     */
    public function view(User $user, Appointment $appointment): bool
    {
        return $user->can('appointments.view');
    }

    /**
     * Determine whether the user can create appointments.
     */
    public function create(User $user): bool
    {
        return $user->can('appointments.create');
    }

    /**
     * Determine whether the user can update an appointment.
     */
    public function update(User $user, Appointment $appointment): bool
    {
        return $user->can('appointments.update');
    }

    /**
     * Determine whether the user can delete an appointment.
     */
    public function delete(User $user, Appointment $appointment): bool
    {
        return $user->can('appointments.delete');
    }

    /**
     * Determine whether the user can restore an appointment.
     */
    public function restore(User $user, Appointment $appointment): bool
    {
        return $user->can('appointments.delete');
    }

    /**
     * Determine whether the user can permanently delete an appointment.
     */
    public function forceDelete(User $user, Appointment $appointment): bool
    {
        return $user->can('appointments.delete');
    }

    /**
     * Determine whether the user can delete appointments in bulk.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('appointments.delete');
    }

    /**
     * Determine whether the user can restore appointments in bulk.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('appointments.delete');
    }

    /**
     * Determine whether the user can permanently delete appointments in bulk.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('appointments.delete');
    }
}
