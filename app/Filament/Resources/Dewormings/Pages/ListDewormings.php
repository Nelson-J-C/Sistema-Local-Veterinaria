<?php

namespace App\Filament\Resources\Dewormings\Pages;

use App\Filament\Resources\Dewormings\DewormingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDewormings extends ListRecords
{
    protected static string $resource = DewormingResource::class;

    public function getTitle(): string
    {
        return 'Desparasitaciones';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Registrar desparasitación'),
        ];
    }
}
