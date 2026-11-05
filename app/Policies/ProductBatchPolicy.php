<?php

namespace App\Policies;

use App\Models\ProductBatch;
use App\Models\User;

class ProductBatchPolicy
{
    /**
     * Determine whether the user can list batches.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('inventory.view');
    }

    /**
     * Determine whether the user can view a batch.
     */
    public function view(User $user, ProductBatch $batch): bool
    {
        return $user->can('inventory.view');
    }

    /**
     * Batches are normally created by a purchase.
     */
    public function create(User $user): bool
    {
        return $user->can('inventory.manage');
    }

    /**
     * Determine whether the user can update a batch.
     */
    public function update(User $user, ProductBatch $batch): bool
    {
        return $user->can('inventory.manage');
    }

    /**
     * Batches with movements are kept for traceability.
     */
    public function delete(User $user, ProductBatch $batch): bool
    {
        return $user->can('inventory.manage') && ! $batch->movements()->exists();
    }

    /**
     * Determine whether the user can restore a batch.
     */
    public function restore(User $user, ProductBatch $batch): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete a batch.
     */
    public function forceDelete(User $user, ProductBatch $batch): bool
    {
        return false;
    }
}
