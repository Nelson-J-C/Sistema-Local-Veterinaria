<?php

namespace App\Filament\Resources\Vaccinations\Pages;

use App\Filament\Resources\Vaccinations\VaccinationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVaccinations extends ListRecords
{
    protected static string $resource = VaccinationResource::class;

    public function getTitle(): string
    {
        return 'Vacunas aplicadas';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Registrar vacuna'),
        ];
    }
}
