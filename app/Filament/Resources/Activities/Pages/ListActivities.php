<?php

namespace App\Filament\Resources\Activities\Pages;

use App\Filament\Resources\Activities\ActivityResource;
use Filament\Resources\Pages\ListRecords;

class ListActivities extends ListRecords
{
    protected static string $resource = ActivityResource::class;

    public function getTitle(): string
    {
        return 'Actividad del sistema';
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
