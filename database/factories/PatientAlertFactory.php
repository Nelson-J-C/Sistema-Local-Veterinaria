<?php

namespace Database\Factories;

use App\Enums\AlertSeverity;
use App\Enums\PatientAlertType;
use App\Models\Patient;
use App\Models\PatientAlert;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PatientAlert>
 */
class PatientAlertFactory extends Factory
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
            'type' => fake()->randomElement(PatientAlertType::cases()),
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->sentence(),
            'severity' => AlertSeverity::Medium,
            'active' => true,
        ];
    }

    /**
     * Indicate that the alert is critical.
     */
    public function critical(): static
    {
        return $this->state(fn (): array => [
            'type' => PatientAlertType::Allergy,
            'severity' => AlertSeverity::High,
        ]);
    }
}
