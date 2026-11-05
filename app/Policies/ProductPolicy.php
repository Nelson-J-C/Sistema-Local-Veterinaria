<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    /**
     * Determine whether the user can list products.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('products.view');
    }

    /**
     * Determine whether the user can view a product.
     */
    public function view(User $user, Product $product): bool
    {
        return $user->can('products.view');
    }

    /**
     * Determine whether the user can create products.
     */
    public function create(User $user): bool
    {
        return $user->can('products.create');
    }

    /**
     * Determine whether the user can update a product's master data.
     */
    public function update(User $user, Product $product): bool
    {
        return $user->can('products.update');
    }

    /**
     * Products with movements or batches are deactivated instead of deleted.
     */
    public function delete(User $user, Product $product): bool
    {
        return $user->can('products.delete')
            && ! $product->movements()->exists()
            && ! $product->batches()->exists();
    }

    /**
     * Determine whether the user can restore a product.
     */
    public function restore(User $user, Product $product): bool
    {
        return $user->can('products.update');
    }

    /**
     * Determine whether the user can permanently delete a product.
     */
    public function forceDelete(User $user, Product $product): bool
    {
        return false;
    }
}
