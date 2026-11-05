<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    /**
     * Determine whether the user can list receipts.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('invoices.view');
    }

    /**
     * Determine whether the user can view a receipt.
     */
    public function view(User $user, Invoice $invoice): bool
    {
        return $user->can('invoices.view');
    }

    /**
     * Determine whether the user can issue a receipt.
     */
    public function create(User $user): bool
    {
        return $user->can('invoices.create');
    }

    /**
     * Issued receipts are immutable; corrections use a void or an addendum.
     */
    public function update(User $user, Invoice $invoice): bool
    {
        return false;
    }

    /**
     * Receipts are kept for the financial audit trail.
     */
    public function delete(User $user, Invoice $invoice): bool
    {
        return false;
    }

    /**
     * Determine whether the user can void an unpaid receipt.
     */
    public function void(User $user, Invoice $invoice): bool
    {
        return $user->can('invoices.void') && $invoice->canBeVoided();
    }

    /**
     * Receipts are never restored manually.
     */
    public function restore(User $user, Invoice $invoice): bool
    {
        return false;
    }

    /**
     * Receipts are never permanently deleted.
     */
    public function forceDelete(User $user, Invoice $invoice): bool
    {
        return false;
    }
}
