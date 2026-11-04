<?php

namespace Database\Factories;

use App\Enums\ReminderChannel;
use App\Enums\ReminderStatus;
use App\Enums\ReminderType;
use App\Models\Deworming;
use App\Models\Patient;
use App\Models\Reminder;
use App\Models\Vaccination;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<Reminder>
 */
class ReminderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reminderable_type' => Vaccination::class,
            'reminderable_id' => Vaccination::factory(),
            'owner_id' => null,
            'patient_id' => null,
            'type' => ReminderType::Vaccination,
            'due_at' => now()->addWeek(),
            'channel' => ReminderChannel::Panel,
            'status' => ReminderStatus::Pending,
            'sent_at' => null,
            'error' => null,
            'notes' => null,
        ];
    }

    /**
     * Attach the reminder to a vaccination and its patient.
     */
    public function forVaccination(Vaccination $vaccination): static
    {
        return $this->state(fn (): array => [
            'reminderable_type' => $vaccination->getMorphClass(),
            'reminderable_id' => $vaccination->getKey(),
            'patient_id' => $vaccination->patient_id,
            'owner_id' => Patient::query()->whereKey($vaccination->patient_id)->value('owner_id'),
            'type' => ReminderType::Vaccination,
            'due_at' => $vaccination->next_due_at ?? now()->addWeek(),
        ]);
    }

    /**
     * Attach the reminder to a deworming and its patient.
     */
    public function forDeworming(Deworming $deworming): static
    {
        return $this->state(fn (): array => [
            'reminderable_type' => $deworming->getMorphClass(),
            'reminderable_id' => $deworming->getKey(),
            'patient_id' => $deworming->patient_id,
            'owner_id' => Patient::query()->whereKey($deworming->patient_id)->value('owner_id'),
            'type' => ReminderType::Deworming,
            'due_at' => $deworming->next_due_at ?? now()->addWeek(),
        ]);
    }

    /**
     * Use an explicit reminderable record.
     */
    public function forReminderable(Model $reminderable): static
    {
        return $this->state(fn (): array => [
            'reminderable_type' => $reminderable->getMorphClass(),
            'reminderable_id' => $reminderable->getKey(),
            'patient_id' => $reminderable->getAttribute('patient_id'),
        ]);
    }

    /**
     * Mark the reminder as already handled.
     */
    public function sent(): static
    {
        return $this->state(fn (): array => [
            'status' => ReminderStatus::Sent,
            'sent_at' => now(),
        ]);
    }
}
