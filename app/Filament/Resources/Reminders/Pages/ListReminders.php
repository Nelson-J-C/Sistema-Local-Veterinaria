<?php

namespace App\Filament\Resources\Reminders\Pages;

use App\Filament\Resources\Reminders\ReminderResource;
use Filament\Resources\Pages\ListRecords;

class ListReminders extends ListRecords
{
    protected static string $resource = ReminderResource::class;

    public function getTitle(): string
    {
        return 'Recordatorios';
    }
}
