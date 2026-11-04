<?php

namespace Database\Factories;

use App\Models\Attachment;
use App\Models\Consultation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attachment>
 */
class AttachmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'attachable_type' => Consultation::class,
            'attachable_id' => Consultation::factory(),
            'patient_id' => null,
            'original_name' => fake()->word().'.pdf',
            'path' => 'attachments/'.fake()->uuid().'.pdf',
            'mime_type' => 'application/pdf',
            'size' => fake()->numberBetween(10_000, 2_000_000),
            'uploaded_by' => User::factory(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
