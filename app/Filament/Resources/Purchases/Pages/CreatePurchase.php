<?php

namespace App\Filament\Resources\Purchases\Pages;

use App\Filament\Resources\Purchases\PurchaseResource;
use App\Models\Purchase;
use App\Services\InventoryService;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchase extends CreateRecord
{
    protected static string $resource = PurchaseResource::class;

    public function getTitle(): string
    {
        return 'Registrar compra';
    }

    /**
     * Register the purchase through the inventory service so stock, batches
     * and movements stay consistent.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Purchase
    {
        return app(InventoryService::class)->receivePurchase($data, (int) auth()->id());
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Compra registrada y stock actualizado';
    }
}
