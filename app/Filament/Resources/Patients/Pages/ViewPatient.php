<?php

namespace App\Filament\Resources\Patients\Pages;

use App\Filament\Resources\Consultations\ConsultationResource;
use App\Filament\Resources\Patients\PatientResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewPatient extends ViewRecord
{
    protected static string $resource = PatientResource::class;

    public function getTitle(): string
    {
        return 'Ficha del paciente';
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('create_consultation')
                ->label('Nueva consulta')
                ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                ->authorize('consultations.create')
                ->url(fn (): string => ConsultationResource::getUrl('create', [
                    'patient_id' => $this->getRecord()->getKey(),
                ])),
        ];
    }
}
