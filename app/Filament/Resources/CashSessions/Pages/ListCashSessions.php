<?php

namespace App\Filament\Resources\CashSessions\Pages;

use App\Filament\Resources\CashSessions\CashSessionResource;
use App\Models\CashSession;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCashSessions extends ListRecords
{
    protected static string $resource = CashSessionResource::class;

    public function getTitle(): string
    {
        return 'Caja';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Abrir caja')
                ->visible(fn (): bool => CashSession::query()->open()->doesntExist()
                    && (auth()->user()?->can('cash.open') ?? false)),
        ];
    }
}
