<?php

namespace Database\Factories;

use App\Enums\DiagnosisType;
use App\Models\Consultation;
use App\Models\ConsultationDiagnosis;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConsultationDiagnosis>
 */
class ConsultationDiagnosisFactory extends Factory
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
            'diagnosis_id' => null,
            'name' => fake()->words(3, true),
            'code' => fake()->optional()->bothify('??-###'),
            'type' => DiagnosisType::Primary,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
