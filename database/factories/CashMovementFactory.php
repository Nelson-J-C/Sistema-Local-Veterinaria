<?php

namespace Database\Factories;

use App\Enums\CashMovementType;
use App\Models\CashMovement;
use App\Models\CashSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashMovement>
 */
class CashMovementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cash_session_id' => CashSession::factory(),
            'type' => CashMovementType::Deposit,
            'amount_cents' => fake()->numberBetween(1000, 10000),
            'description' => ucfirst(fake()->words(3, true)),
            'reference' => null,
            'payment_id' => null,
            'user_id' => null,
            'occurred_at' => now(),
        ];
    }
}
