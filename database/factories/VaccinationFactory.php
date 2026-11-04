<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\User;
use App\Models\Vaccination;
use App\Models\Vaccine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vaccination>
 */
class VaccinationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $administeredAt = now()->subMonth();

        return [
            'patient_id' => Patient::factory(),
            'consultation_id' => null,
            'vaccine_id' => Vaccine::factory(),
            'vet_id' => User::factory(),
            'administered_at' => $administeredAt,
            'next_due_at' => $administeredAt->copy()->addYear(),
            'batch_number' => strtoupper(fake()->bothify('LOTE-####')),
            'manufacturer' => fake()->company(),
            'dose' => '1 ml',
            'notes' => null,
            'created_by' => null,
        ];
    }

    /**
     * Link the vaccination to an existing patient.
     */
    public function forPatient(Patient $patient): static
    {
        return $this->state(fn (): array => [
            'patient_id' => $patient->getKey(),
        ]);
    }

    /**
     * Mark the next dose as overdue.
     */
    public function overdue(): static
    {
        return $this->state(fn (): array => [
            'next_due_at' => now()->subDays(15),
        ]);
    }
}
