<?php

namespace Database\Factories;

use App\Models\Deworming;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Deworming>
 */
class DewormingFactory extends Factory
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
            'product_id' => null,
            'product_name' => fake()->randomElement(['Albendazol', 'Praziquantel', 'Ivermectina']),
            'vet_id' => User::factory(),
            'administered_at' => $administeredAt,
            'next_due_at' => $administeredAt->copy()->addMonths(3),
            'weight_kg' => fake()->randomFloat(2, 1, 40),
            'dose' => '1 tableta',
            'notes' => null,
            'created_by' => null,
        ];
    }

    /**
     * Link the deworming to an existing patient.
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
