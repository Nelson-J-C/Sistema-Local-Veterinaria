<?php

namespace App\Filament\Resources\Vaccines\Pages;

use App\Filament\Resources\Vaccines\VaccineResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVaccines extends ListRecords
{
    protected static string $resource = VaccineResource::class;

    public function getTitle(): string
    {
        return 'Catálogo de vacunas';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nueva vacuna'),
        ];
    }
}
