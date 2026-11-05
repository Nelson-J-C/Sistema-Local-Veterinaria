<?php

namespace App\Policies;

use App\Models\CashMovement;
use App\Models\User;

class CashMovementPolicy
{
    /**
     * Determine whether the user can list cash movements.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('cash.view');
    }

    /**
     * Determine whether the user can view a cash movement.
     */
    public function view(User $user, CashMovement $movement): bool
    {
        return $user->can('cash.view');
    }

    /**
     * Manual movements are registered while the session is open.
     */
    public function create(User $user): bool
    {
        return $user->can('cash.open');
    }

    /**
     * Cash movements are immutable.
     */
    public function update(User $user, CashMovement $movement): bool
    {
        return false;
    }

    /**
     * Cash movements are immutable.
     */
    public function delete(User $user, CashMovement $movement): bool
    {
        return false;
    }
}
