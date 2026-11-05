<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Resources\Invoices\InvoiceResource;
use App\Models\Invoice;
use App\Services\BillingService;
use Filament\Resources\Pages\CreateRecord;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    public function getTitle(): string
    {
        return 'Emitir recibo';
    }

    /**
     * Issue the receipt through the billing service so numbering, totals and
     * item links stay consistent.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Invoice
    {
        return app(BillingService::class)->issueInvoice($data, (int) auth()->id());
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Recibo emitido';
    }
}
