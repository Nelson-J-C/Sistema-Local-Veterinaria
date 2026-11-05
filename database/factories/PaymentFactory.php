<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'amount_cents' => fake()->numberBetween(1000, 10000),
            'method' => PaymentMethod::Transfer,
            'reference' => null,
            'paid_at' => now(),
            'received_by' => null,
            'cash_session_id' => null,
            'notes' => null,
        ];
    }

    /**
     * Cash payment.
     */
    public function cash(): static
    {
        return $this->state(fn (): array => [
            'method' => PaymentMethod::Cash,
        ]);
    }
}
