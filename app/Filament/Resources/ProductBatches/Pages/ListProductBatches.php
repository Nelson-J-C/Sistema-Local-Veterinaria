<?php

namespace App\Filament\Resources\ProductBatches\Pages;

use App\Filament\Resources\ProductBatches\ProductBatchResource;
use Filament\Resources\Pages\ListRecords;

class ListProductBatches extends ListRecords
{
    protected static string $resource = ProductBatchResource::class;

    public function getTitle(): string
    {
        return 'Lotes y caducidades';
    }
}
