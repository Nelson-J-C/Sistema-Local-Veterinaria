<?php

namespace App\Policies;

use App\Models\ProductCategory;
use App\Models\User;

class ProductCategoryPolicy
{
    /**
     * Determine whether the user can list product categories.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('products.view');
    }

    /**
     * Determine whether the user can view a product category.
     */
    public function view(User $user, ProductCategory $category): bool
    {
        return $user->can('products.view');
    }

    /**
     * Determine whether the user can create product categories.
     */
    public function create(User $user): bool
    {
        return $user->can('products.create');
    }

    /**
     * Determine whether the user can update a product category.
     */
    public function update(User $user, ProductCategory $category): bool
    {
        return $user->can('products.update');
    }

    /**
     * Categories with products are kept to preserve the catalog history.
     */
    public function delete(User $user, ProductCategory $category): bool
    {
        return $user->can('products.delete') && ! $category->products()->exists();
    }

    /**
     * Determine whether the user can restore a product category.
     */
    public function restore(User $user, ProductCategory $category): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete a product category.
     */
    public function forceDelete(User $user, ProductCategory $category): bool
    {
        return false;
    }
}
