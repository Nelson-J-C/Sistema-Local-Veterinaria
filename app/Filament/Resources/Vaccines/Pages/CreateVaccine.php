<?php

namespace App\Filament\Resources\Vaccines\Pages;

use App\Filament\Resources\Vaccines\VaccineResource;
use Filament\Resources\Pages\CreateRecord;

class CreateVaccine extends CreateRecord
{
    protected static string $resource = VaccineResource::class;

    public function getTitle(): string
    {
        return 'Nueva vacuna';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Vacuna agregada al catálogo';
    }
}
