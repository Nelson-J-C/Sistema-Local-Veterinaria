<?php

namespace Database\Factories;

use App\Enums\Sex;
use App\Models\Owner;
use App\Models\Patient;
use App\Models\Species;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Patient>
 */
class PatientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'owner_id' => Owner::factory(),
            'name' => fake()->firstName(),
            'species_id' => Species::factory(),
            'breed_id' => null,
            'sex' => fake()->randomElement(Sex::cases()),
            'birth_date' => fake()->dateTimeBetween('-15 years', '-2 months'),
            'color' => fake()->safeColorName(),
            'weight_kg' => fake()->randomFloat(2, 1, 40),
            'microchip' => null,
            'sterilized' => fake()->boolean(),
            'active' => true,
        ];
    }

    /**
     * Indicate that the patient is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'active' => false,
        ]);
    }

    /**
     * Indicate that the patient has died.
     */
    public function deceased(): static
    {
        return $this->state(fn (): array => [
            'deceased_at' => now(),
            'active' => false,
        ]);
    }
}
