<?php

namespace App\Policies;

use App\Models\Owner;
use App\Models\User;

class OwnerPolicy
{
    /**
     * Determine whether the user can list owners.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('owners.view');
    }

    /**
     * Determine whether the user can view an owner.
     */
    public function view(User $user, Owner $owner): bool
    {
        return $user->can('owners.view');
    }

    /**
     * Determine whether the user can create owners.
     */
    public function create(User $user): bool
    {
        return $user->can('owners.create');
    }

    /**
     * Determine whether the user can update an owner.
     */
    public function update(User $user, Owner $owner): bool
    {
        return $user->can('owners.update');
    }

    /**
     * Determine whether the user can soft delete an owner.
     */
    public function delete(User $user, Owner $owner): bool
    {
        return $user->can('owners.delete');
    }

    /**
     * Determine whether the user can restore an owner.
     */
    public function restore(User $user, Owner $owner): bool
    {
        return $user->can('owners.delete');
    }

    /**
     * Determine whether the user can permanently delete an owner.
     */
    public function forceDelete(User $user, Owner $owner): bool
    {
        return $user->can('owners.delete');
    }

    /**
     * Determine whether the user can soft delete owners in bulk.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('owners.delete');
    }

    /**
     * Determine whether the user can restore owners in bulk.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('owners.delete');
    }

    /**
     * Determine whether the user can permanently delete owners in bulk.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('owners.delete');
    }
}
