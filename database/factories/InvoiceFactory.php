<?php

namespace Database\Factories;

use App\Enums\InvoiceDocumentType;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Owner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->numberBetween(5000, 50000);
        $tax = (int) round($subtotal * 0.13);

        return [
            'document_type' => InvoiceDocumentType::Receipt,
            'number' => strtoupper(fake()->unique()->bothify('REC-####-######')),
            'owner_id' => Owner::factory(),
            'patient_id' => null,
            'status' => InvoiceStatus::Issued,
            'issued_at' => now(),
            'due_at' => null,
            'subtotal_cents' => $subtotal,
            'discount_cents' => 0,
            'tax_cents' => $tax,
            'total_cents' => $subtotal + $tax,
            'paid_cents' => 0,
            'balance_cents' => $subtotal + $tax,
            'notes' => null,
            'created_by' => null,
            'voided_at' => null,
            'voided_by' => null,
            'void_reason' => null,
        ];
    }

    /**
     * Receipt that has been fully paid.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => InvoiceStatus::Paid,
            'paid_cents' => $attributes['total_cents'],
            'balance_cents' => 0,
        ]);
    }

    /**
     * Voided receipt.
     */
    public function voided(): static
    {
        return $this->state(fn (): array => [
            'status' => InvoiceStatus::Void,
            'balance_cents' => 0,
            'voided_at' => now(),
            'void_reason' => 'Anulado en pruebas',
        ]);
    }
}
