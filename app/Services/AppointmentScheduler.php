<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\User;
use App\Models\VetTimeOff;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppointmentScheduler
{
    public function __construct(private readonly ClinicSchedule $schedule) {}

    /**
     * Validate and create an appointment while locking the veterinarian agenda.
     *
     * @param  array<string, mixed>  $data
     */
    public function book(array $data): Appointment
    {
        return DB::transaction(function () use ($data): Appointment {
            $this->lockVet((int) $data['vet_id']);

            $status = $data['status'] ?? AppointmentStatus::Scheduled;

            if (! $status instanceof AppointmentStatus) {
                $status = AppointmentStatus::tryFrom((string) $status) ?? AppointmentStatus::Scheduled;
            }

            $this->assertBookable(
                vetId: (int) $data['vet_id'],
                startsAt: Carbon::parse($data['starts_at']),
                endsAt: Carbon::parse($data['ends_at']),
                status: $status,
            );

            $data['status'] = $status;

            return Appointment::query()->create($data);
        });
    }

    /**
     * Validate and update an appointment while locking the veterinarian agenda.
     *
     * @param  array<string, mixed>  $data
     */
    public function reschedule(Appointment $appointment, array $data): Appointment
    {
        return DB::transaction(function () use ($appointment, $data): Appointment {
            $this->lockVet((int) $data['vet_id']);

            $this->assertBookable(
                vetId: (int) $data['vet_id'],
                startsAt: Carbon::parse($data['starts_at']),
                endsAt: Carbon::parse($data['ends_at']),
                status: $appointment->status,
                ignoreAppointmentId: $appointment->getKey(),
                originalStartsAt: $appointment->starts_at,
            );

            $appointment->update($data);

            return $appointment;
        });
    }

    /**
     * Validate the scheduling rules and return every violation found.
     *
     * @return array<int, string>
     */
    public function validate(
        int $vetId,
        CarbonInterface $startsAt,
        CarbonInterface $endsAt,
        AppointmentStatus $status,
        ?int $ignoreAppointmentId = null,
        ?CarbonInterface $originalStartsAt = null,
    ): array {
        $errors = [];

        if (! $endsAt->greaterThan($startsAt)) {
            return ['La hora de fin debe ser posterior a la hora de inicio.'];
        }

        if (! $status->isActive()) {
            return $errors;
        }

        $isMoved = $originalStartsAt === null || ! $originalStartsAt->equalTo($startsAt);

        if ($isMoved && $startsAt->isPast()) {
            $errors[] = 'No se puede agendar una cita en una fecha u hora pasada.';
        }

        $holiday = $this->schedule->holidayFor($startsAt);

        if ($holiday !== null) {
            $errors[] = "La fecha seleccionada es feriado: {$holiday['name']}.";
        }

        if (! $this->schedule->isWithinHours($startsAt, $endsAt)) {
            $errors[] = 'La cita debe estar dentro del horario de atención de la clínica.';
        }

        if ($this->hasVetTimeOff($vetId, $startsAt, $endsAt)) {
            $errors[] = 'El veterinario tiene un bloqueo de agenda en ese horario.';
        }

        if ($this->hasOverlappingAppointment($vetId, $startsAt, $endsAt, $ignoreAppointmentId)) {
            $errors[] = 'El veterinario ya tiene otra cita activa en ese horario.';
        }

        return $errors;
    }

    /**
     * Throw a validation exception when the appointment cannot be booked.
     */
    public function assertBookable(
        int $vetId,
        CarbonInterface $startsAt,
        CarbonInterface $endsAt,
        AppointmentStatus $status,
        ?int $ignoreAppointmentId = null,
        ?CarbonInterface $originalStartsAt = null,
    ): void {
        $errors = $this->validate($vetId, $startsAt, $endsAt, $status, $ignoreAppointmentId, $originalStartsAt);

        if ($errors !== []) {
            throw ValidationException::withMessages([
                'starts_at' => $errors,
            ]);
        }
    }

    /**
     * Get the free start times for a veterinarian on a date.
     *
     * @return Collection<int, Carbon>
     */
    public function availableSlots(User $vet, CarbonInterface $date, ?int $durationMinutes = null): Collection
    {
        $hours = $this->schedule->hoursFor($date);

        if ($hours === null || $this->schedule->isHoliday($date)) {
            return collect();
        }

        $duration = $durationMinutes ?? (int) config('clinic.appointment_duration', 30);
        $open = Carbon::parse("{$date->format('Y-m-d')} {$hours['open']}", $date->getTimezone());
        $close = Carbon::parse("{$date->format('Y-m-d')} {$hours['close']}", $date->getTimezone());

        $appointments = Appointment::query()
            ->where('vet_id', $vet->getKey())
            ->active()
            ->where('starts_at', '<', $close)
            ->where('ends_at', '>', $open)
            ->get();

        $timeOffs = VetTimeOff::query()
            ->where('user_id', $vet->getKey())
            ->where('starts_at', '<', $close)
            ->where('ends_at', '>', $open)
            ->get();

        $slots = collect();

        for ($slot = $open->copy(); $slot->copy()->addMinutes($duration)->lessThanOrEqualTo($close); $slot->addMinutes($duration)) {
            $slotEnd = $slot->copy()->addMinutes($duration);

            $isBusy = $appointments->contains(
                fn (Appointment $appointment): bool => $appointment->starts_at->lessThan($slotEnd)
                    && $appointment->ends_at->greaterThan($slot),
            ) || $timeOffs->contains(
                fn (VetTimeOff $timeOff): bool => $timeOff->starts_at->lessThan($slotEnd)
                    && $timeOff->ends_at->greaterThan($slot),
            );

            if (! $isBusy) {
                $slots->push($slot->copy());
            }
        }

        return $slots;
    }

    /**
     * Lock the veterinarian row so concurrent bookings are serialized.
     */
    private function lockVet(int $vetId): void
    {
        User::query()->whereKey($vetId)->lockForUpdate()->firstOrFail();
    }

    /**
     * Determine whether the veterinarian has a time off block in the range.
     */
    private function hasVetTimeOff(int $vetId, CarbonInterface $startsAt, CarbonInterface $endsAt): bool
    {
        return VetTimeOff::query()
            ->where('user_id', $vetId)
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->exists();
    }

    /**
     * Determine whether the veterinarian has another active appointment in the range.
     */
    private function hasOverlappingAppointment(
        int $vetId,
        CarbonInterface $startsAt,
        CarbonInterface $endsAt,
        ?int $ignoreAppointmentId,
    ): bool {
        return Appointment::query()
            ->where('vet_id', $vetId)
            ->active()
            ->when($ignoreAppointmentId, fn ($query) => $query->whereKeyNot($ignoreAppointmentId))
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->exists();
    }
}
