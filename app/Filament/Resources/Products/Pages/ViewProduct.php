<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\Actions\ProductActions;
use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewProduct extends ViewRecord
{
    protected static string $resource = ProductResource::class;

    public function getTitle(): string
    {
        return 'Detalle del producto';
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            ProductActions::adjustStock(),
        ];
    }
}
