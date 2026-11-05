<?php

namespace App\Filament\Resources\CashSessions\Pages;

use App\Filament\Resources\CashSessions\Actions\CashSessionActions;
use App\Filament\Resources\CashSessions\CashSessionResource;
use Filament\Resources\Pages\ViewRecord;

class ViewCashSession extends ViewRecord
{
    protected static string $resource = CashSessionResource::class;

    public function getTitle(): string
    {
        return 'Detalle de caja';
    }

    protected function getHeaderActions(): array
    {
        return [
            CashSessionActions::recordMovement(),
            CashSessionActions::close(),
        ];
    }
}
