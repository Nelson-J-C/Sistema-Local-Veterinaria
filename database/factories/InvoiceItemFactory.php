<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceItem>
 */
class InvoiceItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 3);
        $unitPrice = fake()->numberBetween(1000, 20000);
        $subtotal = $quantity * $unitPrice;
        $tax = (int) round($subtotal * 0.13);

        return [
            'invoice_id' => Invoice::factory(),
            'product_id' => null,
            'service_id' => null,
            'description' => ucfirst(fake()->words(3, true)),
            'quantity' => $quantity,
            'unit_price_cents' => $unitPrice,
            'discount_cents' => 0,
            'tax_rate' => 13,
            'line_subtotal_cents' => $subtotal,
            'line_tax_cents' => $tax,
            'line_total_cents' => $subtotal + $tax,
            'sort_order' => 0,
        ];
    }
}
