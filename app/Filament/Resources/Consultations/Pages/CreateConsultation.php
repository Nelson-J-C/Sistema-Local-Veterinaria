<?php

namespace App\Filament\Resources\Consultations\Pages;

use App\Filament\Resources\Consultations\ConsultationResource;
use App\Models\Appointment;
use App\Services\ConsultationWorkflow;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateConsultation extends CreateRecord
{
    protected static string $resource = ConsultationResource::class;

    public function getTitle(): string
    {
        return 'Nueva consulta';
    }

    public function mount(): void
    {
        parent::mount();

        $appointmentId = request()->integer('appointment_id');

        if ($appointmentId > 0) {
            $appointment = Appointment::query()
                ->with('patient')
                ->find($appointmentId);

            if ($appointment !== null) {
                $this->form->fill([
                    'appointment_id' => $appointment->getKey(),
                    'patient_id' => $appointment->patient_id,
                    'vet_id' => $appointment->vet_id,
                    'consulted_at' => now()->format('Y-m-d H:i:s'),
                    'chief_complaint' => $appointment->reason ?: 'Consulta',
                ]);

                return;
            }
        }

        $patientId = request()->integer('patient_id');

        if ($patientId > 0) {
            $this->form->fill([
                'patient_id' => $patientId,
                'consulted_at' => now()->format('Y-m-d H:i:s'),
            ]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return app(ConsultationWorkflow::class)->create($data, (int) auth()->id());
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Consulta creada';
    }
}
