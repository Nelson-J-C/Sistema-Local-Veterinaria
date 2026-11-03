<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\VetTimeOff;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VetTimeOff>
 */
class VetTimeOffFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = now()->addDays(2)->setTime(9, 0);

        return [
            'user_id' => User::factory(),
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addHours(3),
            'reason' => fake()->sentence(3),
            'created_by' => null,
        ];
    }
}
