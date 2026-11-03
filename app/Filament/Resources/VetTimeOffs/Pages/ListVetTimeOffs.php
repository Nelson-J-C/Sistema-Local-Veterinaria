<?php

namespace App\Filament\Resources\VetTimeOffs\Pages;

use App\Filament\Resources\VetTimeOffs\VetTimeOffResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVetTimeOffs extends ListRecords
{
    protected static string $resource = VetTimeOffResource::class;

    public function getTitle(): string
    {
        return 'Bloqueos de agenda';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nuevo bloqueo'),
        ];
    }
}
