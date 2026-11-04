<?php

namespace App\Filament\Resources\Vaccinations\Pages;

use App\Filament\Resources\Vaccinations\VaccinationResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditVaccination extends EditRecord
{
    protected static string $resource = VaccinationResource::class;

    public function getTitle(): string
    {
        return 'Editar vacunación';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->update($data);

        return $record;
    }
}
