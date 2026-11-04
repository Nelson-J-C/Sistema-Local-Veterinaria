<?php

namespace App\Services;

use App\Enums\ReminderChannel;
use App\Enums\ReminderStatus;
use App\Enums\ReminderType;
use App\Models\Deworming;
use App\Models\Patient;
use App\Models\Reminder;
use App\Models\Vaccination;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReminderScheduler
{
    /**
     * Keep the pending panel reminder of a vaccination in sync with its next dose.
     */
    public function syncVaccination(Vaccination $vaccination): ?Reminder
    {
        $vaccineName = $vaccination->vaccine()->value('name') ?? 'Vacuna';

        return $this->sync(
            record: $vaccination,
            type: ReminderType::Vaccination,
            dueAt: $vaccination->next_due_at,
            notes: "Vacuna: {$vaccineName}",
        );
    }

    /**
     * Keep the pending panel reminder of a deworming in sync with its next dose.
     */
    public function syncDeworming(Deworming $deworming): ?Reminder
    {
        return $this->sync(
            record: $deworming,
            type: ReminderType::Deworming,
            dueAt: $deworming->next_due_at,
            notes: "Desparasitación: {$deworming->product_name}",
        );
    }

    /**
     * Remove pending reminders for a record that no longer has a due date.
     */
    public function clear(Model $record, ReminderType $type): void
    {
        Reminder::query()
            ->where('reminderable_type', $record->getMorphClass())
            ->where('reminderable_id', $record->getKey())
            ->where('type', $type)
            ->where('channel', ReminderChannel::Panel)
            ->where('status', ReminderStatus::Pending)
            ->delete();
    }

    /**
     * Create or update the single panel reminder for a record and due date.
     */
    private function sync(Model $record, ReminderType $type, mixed $dueAt, string $notes): ?Reminder
    {
        return DB::transaction(function () use ($record, $type, $dueAt, $notes): ?Reminder {
            if ($dueAt === null) {
                $this->clear($record, $type);

                return null;
            }

            $dueAt = Carbon::parse($dueAt);
            $patientId = (int) $record->getAttribute('patient_id');
            $ownerId = Patient::query()->whereKey($patientId)->value('owner_id');

            $existing = Reminder::query()
                ->where('reminderable_type', $record->getMorphClass())
                ->where('reminderable_id', $record->getKey())
                ->where('type', $type)
                ->where('channel', ReminderChannel::Panel)
                ->where('due_at', $dueAt)
                ->first();

            if ($existing instanceof Reminder) {
                $existing->update([
                    'owner_id' => $ownerId,
                    'patient_id' => $patientId,
                    'notes' => $notes,
                ]);

                return $existing;
            }

            $this->clear($record, $type);

            return Reminder::query()->create([
                'reminderable_type' => $record->getMorphClass(),
                'reminderable_id' => $record->getKey(),
                'owner_id' => $ownerId,
                'patient_id' => $patientId,
                'type' => $type,
                'due_at' => $dueAt,
                'channel' => ReminderChannel::Panel,
                'status' => ReminderStatus::Pending,
                'notes' => $notes,
            ]);
        });
    }
}
