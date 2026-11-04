<?php

namespace App\Filament\Resources\Appointments\Actions;

use App\Filament\Resources\Consultations\ConsultationResource;
use App\Models\Appointment;
use App\Services\ConsultationWorkflow;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

class AppointmentActions
{
    public static function startConsultation(): Action
    {
        return Action::make('start_consultation')
            ->label('Iniciar consulta')
            ->icon(Heroicon::OutlinedClipboardDocumentCheck)
            ->color('primary')
            ->authorize('consultations.create')
            ->visible(fn (Appointment $record): bool => $record->consultation === null
                && $record->status->isActive())
            ->action(function (Appointment $record, Action $action): void {
                $consultation = app(ConsultationWorkflow::class)->startFromAppointment(
                    appointment: $record,
                    userId: (int) auth()->id(),
                );

                Notification::make()
                    ->success()
                    ->title('Consulta iniciada')
                    ->body('Continue el registro clínico del paciente.')
                    ->send();

                $livewire = $action->getLivewire();

                if (method_exists($livewire, 'redirect')) {
                    $livewire->redirect(ConsultationResource::getUrl('edit', ['record' => $consultation]));
                }
            });
    }
}
