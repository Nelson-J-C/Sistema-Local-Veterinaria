<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    /**
     * Determine whether the user can list payments.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('payments.view');
    }

    /**
     * Determine whether the user can view a payment.
     */
    public function view(User $user, Payment $payment): bool
    {
        return $user->can('payments.view');
    }

    /**
     * Determine whether the user can register a payment.
     */
    public function create(User $user): bool
    {
        return $user->can('payments.create');
    }

    /**
     * Payments are immutable once registered.
     */
    public function update(User $user, Payment $payment): bool
    {
        return false;
    }

    /**
     * Payments are kept for the financial audit trail.
     */
    public function delete(User $user, Payment $payment): bool
    {
        return false;
    }
}
