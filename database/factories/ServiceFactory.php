<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('SRV-####')),
            'name' => fake()->words(3, true),
            'category' => 'general',
            'description' => fake()->sentence(),
            'price_cents' => fake()->numberBetween(1000, 100000),
            'tax_rate' => config('clinic.tax_rate'),
            'duration_minutes' => 30,
            'active' => true,
        ];
    }
}
