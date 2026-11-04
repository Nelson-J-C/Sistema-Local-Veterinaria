<?php

namespace App\Filament\Resources\Appointments\Pages;

use App\Filament\Resources\Appointments\Actions\AppointmentActions;
use App\Filament\Resources\Appointments\AppointmentResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAppointment extends ViewRecord
{
    protected static string $resource = AppointmentResource::class;

    public function getTitle(): string
    {
        return 'Detalle de la cita';
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            AppointmentActions::startConsultation(),
            DeleteAction::make(),
        ];
    }
}
