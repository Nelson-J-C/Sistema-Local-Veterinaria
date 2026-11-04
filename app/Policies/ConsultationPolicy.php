<?php

namespace App\Policies;

use App\Models\Consultation;
use App\Models\User;

class ConsultationPolicy
{
    /**
     * Determine whether the user can list consultations.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('consultations.view');
    }

    /**
     * Determine whether the user can view a consultation.
     */
    public function view(User $user, Consultation $consultation): bool
    {
        return $user->can('consultations.view');
    }

    /**
     * Determine whether the user can create consultations.
     */
    public function create(User $user): bool
    {
        return $user->can('consultations.create');
    }

    /**
     * Only draft consultations can be updated; finalized records use addenda.
     */
    public function update(User $user, Consultation $consultation): bool
    {
        return $user->can('consultations.update') && $consultation->isDraft();
    }

    /**
     * Draft consultations can be discarded before they become legal records.
     */
    public function delete(User $user, Consultation $consultation): bool
    {
        return $user->can('consultations.update') && $consultation->isDraft();
    }

    /**
     * Finalize a draft consultation.
     */
    public function finalize(User $user, Consultation $consultation): bool
    {
        return $user->can('consultations.finalize') && $consultation->isDraft();
    }

    /**
     * Add a correction to a finalized or amended consultation.
     */
    public function addendum(User $user, Consultation $consultation): bool
    {
        return $user->can('consultations.update') && $consultation->isLocked();
    }

    /**
     * Finalized clinical records are never restored through the panel.
     */
    public function restore(User $user, Consultation $consultation): bool
    {
        return false;
    }

    /**
     * Finalized clinical records are never permanently deleted.
     */
    public function forceDelete(User $user, Consultation $consultation): bool
    {
        return false;
    }
}
