<?php

namespace App\Filament\Resources\Consultations\Pages;

use App\Filament\Resources\Consultations\Actions\ConsultationActions;
use App\Filament\Resources\Consultations\ConsultationResource;
use App\Models\Consultation;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewConsultation extends ViewRecord
{
    protected static string $resource = ConsultationResource::class;

    public function getTitle(): string
    {
        return 'Consulta clínica';
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn (Consultation $record): bool => $record->isDraft()),
            ConsultationActions::finalize(),
            ConsultationActions::addendum(),
        ];
    }
}
