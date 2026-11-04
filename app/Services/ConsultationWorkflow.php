<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\ConsultationStatus;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\ConsultationAddendum;
use App\Models\PatientMeasurement;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConsultationWorkflow
{
    /**
     * Create a draft consultation, optionally linked to an appointment.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, int $userId): Consultation
    {
        return DB::transaction(function () use ($data, $userId): Consultation {
            $appointment = null;

            if (filled($data['appointment_id'] ?? null)) {
                $appointment = Appointment::query()
                    ->with('patient')
                    ->lockForUpdate()
                    ->findOrFail($data['appointment_id']);

                $this->assertAppointmentCanStart($appointment);

                $data['patient_id'] = $appointment->patient_id;
                $data['vet_id'] = $appointment->vet_id;
                $data['consulted_at'] = $data['consulted_at'] ?? now();
            }

            $data['consulted_at'] = Carbon::parse($data['consulted_at'] ?? now());
            $data['status'] = ConsultationStatus::Draft;
            $data['finalized_at'] = null;
            $data['finalized_by'] = null;
            $data['created_by'] = $userId;

            $consultation = Consultation::query()->create($data);

            if ($appointment instanceof Appointment) {
                $appointment->update(['status' => AppointmentStatus::InProgress]);
            }

            return $consultation;
        });
    }

    /**
     * Start a consultation from the agenda, using the appointment context.
     */
    public function startFromAppointment(Appointment $appointment, int $userId): Consultation
    {
        return $this->create([
            'appointment_id' => $appointment->getKey(),
            'patient_id' => $appointment->patient_id,
            'vet_id' => $appointment->vet_id,
            'consulted_at' => now(),
            'chief_complaint' => $appointment->reason ?: 'Consulta',
        ], $userId);
    }

    /**
     * Finalize and lock a consultation, completing its appointment.
     */
    public function finalize(Consultation $consultation, int $userId): Consultation
    {
        return DB::transaction(function () use ($consultation, $userId): Consultation {
            $consultation = Consultation::query()
                ->lockForUpdate()
                ->findOrFail($consultation->getKey());

            if (! $consultation->isDraft()) {
                throw ValidationException::withMessages([
                    'consultation' => 'Solo las consultas en borrador pueden finalizarse.',
                ]);
            }

            $consultation->update([
                'status' => ConsultationStatus::Finalized,
                'finalized_at' => now(),
                'finalized_by' => $userId,
            ]);

            $appointment = $consultation->appointment()->lockForUpdate()->first();

            if ($appointment instanceof Appointment
                && ! in_array($appointment->status, [AppointmentStatus::Cancelled, AppointmentStatus::NoShow], true)) {
                $appointment->update(['status' => AppointmentStatus::Completed]);
            }

            $this->syncPatientWeight($consultation);

            return $consultation->refresh();
        });
    }

    /**
     * Append a correction to a finalized consultation.
     */
    public function addendum(Consultation $consultation, string $content, int $userId): ConsultationAddendum
    {
        return DB::transaction(function () use ($consultation, $content, $userId): ConsultationAddendum {
            $consultation = Consultation::query()
                ->lockForUpdate()
                ->findOrFail($consultation->getKey());

            if (! $consultation->isLocked()) {
                throw ValidationException::withMessages([
                    'addendum' => 'La consulta debe estar finalizada antes de agregar un addendum.',
                ]);
            }

            $addendum = $consultation->addenda()->create([
                'content' => $content,
                'created_by' => $userId,
            ]);

            if ($consultation->status === ConsultationStatus::Finalized) {
                $consultation->update(['status' => ConsultationStatus::Amended]);
            }

            return $addendum;
        });
    }

    /**
     * Prevent starting a consultation for a closed or already documented appointment.
     */
    private function assertAppointmentCanStart(Appointment $appointment): void
    {
        if (in_array($appointment->status, [
            AppointmentStatus::Cancelled,
            AppointmentStatus::NoShow,
            AppointmentStatus::Completed,
        ], true)) {
            throw ValidationException::withMessages([
                'appointment_id' => 'No se puede iniciar una consulta para una cita cerrada.',
            ]);
        }

        if ($appointment->consultation()->exists()) {
            throw ValidationException::withMessages([
                'appointment_id' => 'La cita ya tiene una consulta registrada.',
            ]);
        }
    }

    /**
     * Keep the patient's current weight in sync with the latest clinical measurement.
     */
    private function syncPatientWeight(Consultation $consultation): void
    {
        $weight = PatientMeasurement::query()
            ->where('consultation_id', $consultation->getKey())
            ->whereNotNull('weight_kg')
            ->latest('measured_at')
            ->value('weight_kg');

        if (filled($weight)) {
            $consultation->patient()->update(['weight_kg' => $weight]);
        }
    }
}
