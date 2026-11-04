<?php

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Enums\ConsultationStatus;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Consultation>
 */
class ConsultationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'appointment_id' => null,
            'vet_id' => User::factory(),
            'consulted_at' => now()->subHour(),
            'chief_complaint' => fake()->sentence(6),
            'subjective' => fake()->paragraph(),
            'objective' => fake()->paragraph(),
            'assessment' => fake()->paragraph(),
            'plan' => fake()->paragraph(),
            'status' => ConsultationStatus::Draft,
            'finalized_at' => null,
            'finalized_by' => null,
            'next_visit_at' => fake()->optional()->dateTimeBetween('now', '+3 months'),
            'created_by' => null,
        ];
    }

    /**
     * Create a consultation linked to an existing appointment.
     */
    public function forAppointment(Appointment $appointment): static
    {
        return $this->state(fn (): array => [
            'patient_id' => $appointment->patient_id,
            'appointment_id' => $appointment->getKey(),
            'vet_id' => $appointment->vet_id,
            'consulted_at' => $appointment->starts_at,
            'chief_complaint' => $appointment->reason ?: fake()->sentence(6),
        ]);
    }

    /**
     * Mark the consultation as finalized.
     */
    public function finalized(?User $user = null): static
    {
        return $this->state(fn (): array => [
            'status' => ConsultationStatus::Finalized,
            'finalized_at' => now(),
            'finalized_by' => $user?->getKey() ?? User::factory(),
        ]);
    }

    /**
     * Mark the consultation as amended with a later addendum.
     */
    public function amended(?User $user = null): static
    {
        return $this->finalized($user)->state(fn (): array => [
            'status' => ConsultationStatus::Amended,
        ]);
    }

    /**
     * Link an existing in-progress appointment while the consultation is a draft.
     */
    public function withInProgressAppointment(Appointment $appointment): static
    {
        $appointment->update(['status' => AppointmentStatus::InProgress]);

        return $this->forAppointment($appointment);
    }
}
