<?php

namespace App\Filament\Resources\Dewormings\Pages;

use App\Filament\Resources\Dewormings\DewormingResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditDeworming extends EditRecord
{
    protected static string $resource = DewormingResource::class;

    public function getTitle(): string
    {
        return 'Editar desparasitación';
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
