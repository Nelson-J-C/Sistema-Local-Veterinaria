<?php

namespace App\Policies;

use App\Models\Purchase;
use App\Models\User;

class PurchasePolicy
{
    /**
     * Determine whether the user can list purchases.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('purchases.view');
    }

    /**
     * Determine whether the user can view a purchase.
     */
    public function view(User $user, Purchase $purchase): bool
    {
        return $user->can('purchases.view');
    }

    /**
     * Determine whether the user can register a purchase.
     */
    public function create(User $user): bool
    {
        return $user->can('purchases.create');
    }

    /**
     * Registered purchases are immutable so stock entries stay traceable.
     */
    public function update(User $user, Purchase $purchase): bool
    {
        return false;
    }

    /**
     * Registered purchases are kept for traceability.
     */
    public function delete(User $user, Purchase $purchase): bool
    {
        return false;
    }
}
