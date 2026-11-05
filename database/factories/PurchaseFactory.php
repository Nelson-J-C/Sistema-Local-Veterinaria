<?php

namespace Database\Factories;

use App\Models\Purchase;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Purchase>
 */
class PurchaseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'supplier_id' => Supplier::factory(),
            'document_number' => strtoupper(fake()->bothify('FACT-####')),
            'purchased_at' => today(),
            'subtotal_cents' => 0,
            'tax_cents' => 0,
            'total_cents' => 0,
            'notes' => null,
            'created_by' => null,
        ];
    }
}
