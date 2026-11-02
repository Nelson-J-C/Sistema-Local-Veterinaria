<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can list users.
     */
    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'users.view');
    }

    /**
     * Determine whether the user can view a user.
     */
    public function view(User $user, User $target): bool
    {
        return $this->hasPermission($user, 'users.view');
    }

    /**
     * Determine whether the user can create users.
     */
    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'users.create');
    }

    /**
     * Determine whether the user can update a user.
     */
    public function update(User $user, User $target): bool
    {
        return $this->hasPermission($user, 'users.update');
    }

    /**
     * Determine whether the user can soft delete a user.
     */
    public function delete(User $user, User $target): bool
    {
        return $this->hasPermission($user, 'users.delete')
            && ! $target->is($user)
            && ! $this->isLastActiveAdministrator($target);
    }

    /**
     * Determine whether the user can restore a soft deleted user.
     */
    public function restore(User $user, User $target): bool
    {
        return $this->hasPermission($user, 'users.delete');
    }

    /**
     * Determine whether the user can restore any user in bulk.
     */
    public function restoreAny(User $user): bool
    {
        return $this->hasPermission($user, 'users.delete');
    }

    /**
     * Determine whether the user can permanently delete a user.
     */
    public function forceDelete(User $user, User $target): bool
    {
        return $user->hasRole('admin')
            && ! $target->is($user)
            && ! $this->isLastActiveAdministrator($target);
    }

    /**
     * Determine whether the user can permanently delete users in bulk.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can soft delete users in bulk.
     */
    public function deleteAny(User $user): bool
    {
        return $this->hasPermission($user, 'users.delete');
    }

    /**
     * Check a permission while keeping the administrator role as a fallback.
     */
    private function hasPermission(User $user, string $permission): bool
    {
        return $user->hasRole('admin') || $user->can($permission);
    }

    /**
     * Prevent removing the only active administrator of the clinic.
     */
    private function isLastActiveAdministrator(User $target): bool
    {
        if (! $target->hasRole('admin') || ! $target->active) {
            return false;
        }

        return ! User::query()
            ->role('admin')
            ->where('active', true)
            ->whereKeyNot($target->getKey())
            ->exists();
    }
}
