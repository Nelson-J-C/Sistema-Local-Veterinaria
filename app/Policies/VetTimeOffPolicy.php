<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VetTimeOff;

class VetTimeOffPolicy
{
    /**
     * Determine whether the user can list time off blocks.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('appointments.view');
    }

    /**
     * Determine whether the user can view a time off block.
     */
    public function view(User $user, VetTimeOff $vetTimeOff): bool
    {
        return $user->can('appointments.view');
    }

    /**
     * Determine whether the user can create time off blocks.
     */
    public function create(User $user): bool
    {
        return $user->can('appointments.update');
    }

    /**
     * Determine whether the user can update a time off block.
     */
    public function update(User $user, VetTimeOff $vetTimeOff): bool
    {
        return $user->can('appointments.update');
    }

    /**
     * Determine whether the user can delete a time off block.
     */
    public function delete(User $user, VetTimeOff $vetTimeOff): bool
    {
        return $user->can('appointments.update');
    }

    /**
     * Determine whether the user can delete time off blocks in bulk.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('appointments.update');
    }
}
