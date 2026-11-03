<?php

namespace App\Filament\Resources\VetTimeOffs\Pages;

use App\Filament\Resources\VetTimeOffs\VetTimeOffResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateVetTimeOff extends CreateRecord
{
    protected static string $resource = VetTimeOffResource::class;

    public function getTitle(): string
    {
        return 'Nuevo bloqueo de agenda';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $data['created_by'] = auth()->id();

        return static::getModel()::create($data);
    }
}
