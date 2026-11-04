<?php

namespace App\Policies;

use App\Models\ConsultationAddendum;
use App\Models\User;

class ConsultationAddendumPolicy
{
    /**
     * Determine whether the user can view addenda.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('consultations.view');
    }

    /**
     * Determine whether the user can view an addendum.
     */
    public function view(User $user, ConsultationAddendum $consultationAddendum): bool
    {
        return $user->can('consultations.view');
    }

    /**
     * Addenda are only allowed once the consultation is finalized.
     */
    public function create(User $user): bool
    {
        return $user->can('consultations.update');
    }

    /**
     * Addenda are append-only and cannot be edited.
     */
    public function update(User $user, ConsultationAddendum $consultationAddendum): bool
    {
        return false;
    }

    /**
     * Addenda are append-only and cannot be deleted.
     */
    public function delete(User $user, ConsultationAddendum $consultationAddendum): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore an addendum.
     */
    public function restore(User $user, ConsultationAddendum $consultationAddendum): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete an addendum.
     */
    public function forceDelete(User $user, ConsultationAddendum $consultationAddendum): bool
    {
        return false;
    }
}
