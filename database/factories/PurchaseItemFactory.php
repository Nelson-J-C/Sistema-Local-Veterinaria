<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseItem>
 */
class PurchaseItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 20);
        $unitCost = fake()->numberBetween(500, 20000);

        return [
            'purchase_id' => Purchase::factory(),
            'product_id' => Product::factory(),
            'batch_number' => strtoupper(fake()->bothify('LOTE-####')),
            'expires_at' => today()->addMonths(10),
            'quantity' => $quantity,
            'unit_cost_cents' => $unitCost,
            'line_total_cents' => $quantity * $unitCost,
        ];
    }
}
