<?php

namespace Database\Factories;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
class StockMovementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'batch_id' => null,
            'type' => StockMovementType::Adjustment,
            'quantity' => 1,
            'unit_cost_cents' => null,
            'reference_type' => null,
            'reference_id' => null,
            'notes' => null,
            'user_id' => null,
            'occurred_at' => now(),
        ];
    }

    /**
     * Movement that added stock.
     */
    public function incoming(float $quantity = 10): static
    {
        return $this->state(fn (): array => [
            'type' => StockMovementType::Purchase,
            'quantity' => $quantity,
        ]);
    }

    /**
     * Movement that removed stock.
     */
    public function outgoing(float $quantity = 1): static
    {
        return $this->state(fn (): array => [
            'type' => StockMovementType::Sale,
            'quantity' => -abs($quantity),
        ]);
    }
}
