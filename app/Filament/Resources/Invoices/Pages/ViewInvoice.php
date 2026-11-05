<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Resources\Invoices\Actions\InvoiceActions;
use App\Filament\Resources\Invoices\InvoiceResource;
use Filament\Resources\Pages\ViewRecord;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    public function getTitle(): string
    {
        return 'Detalle del recibo';
    }

    protected function getHeaderActions(): array
    {
        return [
            InvoiceActions::print(),
            InvoiceActions::recordPayment(),
            InvoiceActions::void(),
        ];
    }
}
