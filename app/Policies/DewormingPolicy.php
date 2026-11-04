<?php

namespace App\Policies;

use App\Models\Deworming;
use App\Models\User;

class DewormingPolicy
{
    /**
     * Determine whether the user can list dewormings.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('dewormings.view');
    }

    /**
     * Determine whether the user can view a deworming.
     */
    public function view(User $user, Deworming $deworming): bool
    {
        return $user->can('dewormings.view');
    }

    /**
     * Determine whether the user can record a deworming.
     */
    public function create(User $user): bool
    {
        return $user->can('dewormings.create');
    }

    /**
     * Determine whether the user can correct a deworming record.
     */
    public function update(User $user, Deworming $deworming): bool
    {
        return $user->can('dewormings.update');
    }

    /**
     * Deworming records are part of the clinical history and are not deleted.
     */
    public function delete(User $user, Deworming $deworming): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore a deworming.
     */
    public function restore(User $user, Deworming $deworming): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete a deworming.
     */
    public function forceDelete(User $user, Deworming $deworming): bool
    {
        return false;
    }
}
