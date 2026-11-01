<?php

namespace Database\Factories;

use App\Models\Species;
use App\Models\Vaccine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vaccine>
 */
class VaccineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'species_id' => Species::factory(),
            'name' => fake()->unique()->words(2, true),
            'manufacturer' => fake()->company(),
            'default_revaccination_days' => 365,
            'active' => true,
        ];
    }
}
