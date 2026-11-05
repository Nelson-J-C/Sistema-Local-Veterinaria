<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductBatch>
 */
class ProductBatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 50);

        return [
            'product_id' => Product::factory(),
            'batch_number' => strtoupper(fake()->bothify('LOTE-####')),
            'expires_at' => today()->addMonths(8),
            'quantity_received' => $quantity,
            'quantity_remaining' => $quantity,
            'unit_cost_cents' => fake()->numberBetween(500, 20000),
            'supplier_id' => Supplier::factory(),
            'received_at' => today(),
            'notes' => null,
        ];
    }

    /**
     * Batch that is about to expire.
     */
    public function expiringSoon(int $days = 15): static
    {
        return $this->state(fn (): array => [
            'expires_at' => today()->addDays($days),
        ]);
    }

    /**
     * Batch that has already expired.
     */
    public function expired(): static
    {
        return $this->state(fn (): array => [
            'expires_at' => today()->subDays(5),
        ]);
    }

    /**
     * Batch with no stock left.
     */
    public function depleted(): static
    {
        return $this->state(fn (): array => [
            'quantity_remaining' => 0,
        ]);
    }
}
