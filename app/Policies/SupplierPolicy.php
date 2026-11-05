<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;

class SupplierPolicy
{
    /**
     * Determine whether the user can list suppliers.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('suppliers.view');
    }

    /**
     * Determine whether the user can view a supplier.
     */
    public function view(User $user, Supplier $supplier): bool
    {
        return $user->can('suppliers.view');
    }

    /**
     * Determine whether the user can create suppliers.
     */
    public function create(User $user): bool
    {
        return $user->can('suppliers.create');
    }

    /**
     * Determine whether the user can update a supplier.
     */
    public function update(User $user, Supplier $supplier): bool
    {
        return $user->can('suppliers.update');
    }

    /**
     * Suppliers with purchases are kept for traceability.
     */
    public function delete(User $user, Supplier $supplier): bool
    {
        return $user->can('suppliers.delete') && ! $supplier->purchases()->exists();
    }

    /**
     * Determine whether the user can restore a supplier.
     */
    public function restore(User $user, Supplier $supplier): bool
    {
        return $user->can('suppliers.update');
    }

    /**
     * Determine whether the user can permanently delete a supplier.
     */
    public function forceDelete(User $user, Supplier $supplier): bool
    {
        return false;
    }
}
