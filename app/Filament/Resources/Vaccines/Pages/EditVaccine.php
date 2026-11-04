<?php

namespace App\Filament\Resources\Vaccines\Pages;

use App\Filament\Resources\Vaccines\VaccineResource;
use Filament\Resources\Pages\EditRecord;

class EditVaccine extends EditRecord
{
    protected static string $resource = VaccineResource::class;

    public function getTitle(): string
    {
        return 'Editar vacuna';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
