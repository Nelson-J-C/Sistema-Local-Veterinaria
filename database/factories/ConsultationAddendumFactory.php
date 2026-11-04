<?php

namespace Database\Factories;

use App\Models\Consultation;
use App\Models\ConsultationAddendum;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConsultationAddendum>
 */
class ConsultationAddendumFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'consultation_id' => Consultation::factory()->finalized(),
            'content' => fake()->paragraph(),
            'created_by' => User::factory(),
        ];
    }
}
