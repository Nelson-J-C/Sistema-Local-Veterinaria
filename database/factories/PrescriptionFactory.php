<?php

namespace Database\Factories;

use App\Enums\MedicationRoute;
use App\Models\Consultation;
use App\Models\Prescription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Prescription>
 */
class PrescriptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'consultation_id' => Consultation::factory(),
            'product_id' => null,
            'medication_name' => fake()->word(),
            'presentation' => fake()->optional()->word(),
            'dose' => fake()->randomElement(['1 ml', '1 tableta', '0,5 ml']),
            'frequency' => fake()->randomElement(['Cada 12 horas', 'Cada 24 horas', 'Cada 8 horas']),
            'route' => MedicationRoute::Oral,
            'duration_days' => fake()->numberBetween(3, 14),
            'quantity' => fake()->randomFloat(2, 1, 30),
            'instructions' => fake()->optional()->sentence(),
            'dispensed' => false,
            'dispensed_at' => null,
            'dispensed_by' => null,
        ];
    }
}
