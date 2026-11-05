<?php

namespace Database\Factories;

use App\Enums\CashSessionStatus;
use App\Models\CashSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashSession>
 */
class CashSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'opened_by' => null,
            'opened_at' => now(),
            'opening_cents' => 0,
            'closed_by' => null,
            'closed_at' => null,
            'expected_cents' => null,
            'counted_cents' => null,
            'difference_cents' => null,
            'status' => CashSessionStatus::Open,
            'notes' => null,
        ];
    }

    /**
     * Closed cash session.
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => CashSessionStatus::Closed,
            'closed_at' => now(),
            'expected_cents' => $attributes['opening_cents'],
            'counted_cents' => $attributes['opening_cents'],
            'difference_cents' => 0,
        ]);
    }
}
