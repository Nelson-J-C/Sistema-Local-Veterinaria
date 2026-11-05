<?php

namespace App\Policies;

use App\Models\StockMovement;
use App\Models\User;

class StockMovementPolicy
{
    /**
     * Determine whether the user can list stock movements.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('inventory.view');
    }

    /**
     * Determine whether the user can view a stock movement.
     */
    public function view(User $user, StockMovement $movement): bool
    {
        return $user->can('inventory.view');
    }

    /**
     * Movements are created through purchases and adjustments.
     */
    public function create(User $user): bool
    {
        return $user->can('inventory.manage');
    }

    /**
     * The stock ledger is immutable.
     */
    public function update(User $user, StockMovement $movement): bool
    {
        return false;
    }

    /**
     * The stock ledger is immutable.
     */
    public function delete(User $user, StockMovement $movement): bool
    {
        return false;
    }
}
