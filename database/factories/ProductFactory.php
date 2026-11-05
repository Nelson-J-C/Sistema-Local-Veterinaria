<?php

namespace Database\Factories;

use App\Enums\ProductUnit;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cost = fake()->numberBetween(500, 20000);

        return [
            'sku' => strtoupper(fake()->unique()->bothify('PROD-####')),
            'barcode' => null,
            'name' => ucfirst(fake()->words(2, true)),
            'category_id' => ProductCategory::factory(),
            'supplier_id' => Supplier::factory(),
            'unit' => ProductUnit::Unit,
            'presentation' => null,
            'concentration' => null,
            'cost_cents' => $cost,
            'price_cents' => (int) round($cost * 1.3),
            'tax_rate' => 13,
            'track_stock' => true,
            'stock_quantity' => 0,
            'min_stock' => 0,
            'max_stock' => null,
            'expiry_alert_days' => 90,
            'active' => true,
            'notes' => null,
        ];
    }

    /**
     * Product that does not track stock (for example a service-like item).
     */
    public function withoutStockControl(): static
    {
        return $this->state(fn (): array => [
            'track_stock' => false,
            'stock_quantity' => 0,
            'min_stock' => 0,
            'max_stock' => null,
        ]);
    }

    /**
     * Product with available stock and a restock threshold.
     */
    public function withStock(float $quantity, float $minStock = 0): static
    {
        return $this->state(fn (): array => [
            'stock_quantity' => $quantity,
            'min_stock' => $minStock,
        ]);
    }

    /**
     * Product with stock below its minimum.
     */
    public function lowStock(): static
    {
        return $this->state(fn (): array => [
            'stock_quantity' => 2,
            'min_stock' => 10,
        ]);
    }

    /**
     * Inactive product.
     */
    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'active' => false,
        ]);
    }
}
