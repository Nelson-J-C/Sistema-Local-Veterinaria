<?php

namespace App\Filament\Resources\CashSessions\Pages;

use App\Filament\Resources\CashSessions\CashSessionResource;
use App\Models\CashSession;
use App\Services\BillingService;
use Filament\Resources\Pages\CreateRecord;

class CreateCashSession extends CreateRecord
{
    protected static string $resource = CashSessionResource::class;

    public function getTitle(): string
    {
        return 'Abrir caja';
    }

    /**
     * Open the session through the billing service so only one open session
     * can exist at a time.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): CashSession
    {
        return app(BillingService::class)->openCashSession(
            userId: (int) auth()->id(),
            openingCents: (int) ($data['opening_cents'] ?? 0),
            notes: $data['notes'] ?? null,
        );
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Caja abierta';
    }
}
