<?php

namespace App\Filament\Resources\VetTimeOffs\Pages;

use App\Filament\Resources\VetTimeOffs\VetTimeOffResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditVetTimeOff extends EditRecord
{
    protected static string $resource = VetTimeOffResource::class;

    public function getTitle(): string
    {
        return 'Editar bloqueo de agenda';
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
