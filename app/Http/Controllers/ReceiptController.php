<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ReceiptController extends Controller
{
    /**
     * Render the printable internal receipt.
     */
    public function __invoke(Invoice $invoice): View
    {
        Gate::authorize('view', $invoice);

        $invoice->load([
            'owner',
            'patient.species',
            'items.service',
            'items.product',
            'payments.receivedBy',
            'createdBy',
            'voidedBy',
        ]);

        return view('invoices.receipt', [
            'invoice' => $invoice,
        ]);
    }
}
