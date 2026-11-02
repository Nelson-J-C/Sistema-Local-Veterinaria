<?php

namespace Database\Factories;

use App\Enums\DocumentType;
use App\Models\Owner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Owner>
 */
class OwnerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'business_name' => null,
            'document_type' => DocumentType::Ci,
            'document_number' => fake()->unique()->numerify('#######'),
            'document_complement' => null,
            'phone' => fake()->numerify('7#######'),
            'phone_alt' => null,
            'email' => fake()->unique()->safeEmail(),
            'address' => fake()->streetAddress(),
            'city' => 'Santa Cruz de la Sierra',
            'department' => 'Santa Cruz',
            'portal_enabled' => false,
            'active' => true,
        ];
    }

    /**
     * Indicate that the owner is a business.
     */
    public function business(): static
    {
        return $this->state(fn (): array => [
            'first_name' => null,
            'last_name' => null,
            'business_name' => fake()->company(),
            'document_type' => DocumentType::Nit,
        ]);
    }

    /**
     * Indicate that the owner is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'active' => false,
        ]);
    }
}
