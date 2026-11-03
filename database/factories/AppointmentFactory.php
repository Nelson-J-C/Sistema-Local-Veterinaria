<?php

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = now()->addDay()->setTime(9, 0);

        return [
            'patient_id' => Patient::factory(),
            'owner_id' => fn (array $attributes): int => Patient::query()
                ->findOrFail($attributes['patient_id'])
                ->owner_id,
            'vet_id' => User::factory(),
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addMinutes(30),
            'type' => AppointmentType::Consultation,
            'status' => AppointmentStatus::Scheduled,
            'reason' => fake()->sentence(4),
            'notes' => null,
            'created_by' => null,
        ];
    }

    /**
     * Indicate that the appointment is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (): array => [
            'status' => AppointmentStatus::Cancelled,
            'cancelled_at' => now(),
            'cancellation_reason' => 'Cancelada por el tutor',
        ]);
    }

    /**
     * Indicate that the appointment has been completed.
     */
    public function completed(): static
    {
        return $this->state(fn (): array => [
            'status' => AppointmentStatus::Completed,
        ]);
    }
}
