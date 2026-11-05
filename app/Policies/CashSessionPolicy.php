<?php

namespace App\Policies;

use App\Models\CashSession;
use App\Models\User;

class CashSessionPolicy
{
    /**
     * Determine whether the user can list cash sessions.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('cash.view');
    }

    /**
     * Determine whether the user can view a cash session.
     */
    public function view(User $user, CashSession $session): bool
    {
        return $user->can('cash.view');
    }

    /**
     * Determine whether the user can open a cash session.
     */
    public function create(User $user): bool
    {
        return $user->can('cash.open');
    }

    /**
     * Determine whether the user can close a cash session.
     */
    public function close(User $user, CashSession $session): bool
    {
        return $user->can('cash.close') && $session->isOpen();
    }

    /**
     * Determine whether the user can register a manual cash movement.
     */
    public function recordMovement(User $user, CashSession $session): bool
    {
        return $user->can('cash.open') && $session->isOpen();
    }

    /**
     * Cash sessions are corrected with movements and closing notes.
     */
    public function update(User $user, CashSession $session): bool
    {
        return false;
    }

    /**
     * Cash sessions are kept for the financial audit trail.
     */
    public function delete(User $user, CashSession $session): bool
    {
        return false;
    }
}
