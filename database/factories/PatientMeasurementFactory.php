<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\PatientMeasurement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PatientMeasurement>
 */
class PatientMeasurementFactory extends Factory
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
            'consultation_id' => null,
            'measured_at' => now(),
            'weight_kg' => fake()->randomFloat(2, 1, 60),
            'temperature_c' => fake()->randomFloat(1, 37, 40),
            'heart_rate' => fake()->numberBetween(60, 180),
            'respiratory_rate' => fake()->numberBetween(12, 60),
            'body_condition_score' => fake()->numberBetween(1, 9),
            'pain_score' => fake()->numberBetween(0, 4),
            'notes' => fake()->optional()->sentence(),
            'recorded_by' => User::factory(),
        ];
    }
}
