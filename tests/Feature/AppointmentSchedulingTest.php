<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Models\Appointment;
use App\Models\Owner;
use App\Models\Patient;
use App\Models\Setting;
use App\Models\User;
use App\Models\VetTimeOff;
use App\Services\AppointmentScheduler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AppointmentSchedulingTest extends TestCase
{
    use RefreshDatabase;

    private User $vet;

    private Owner $owner;

    private Patient $patient;

    private AppointmentScheduler $scheduler;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-11-02 08:00:00'));
        $this->travelTo(Carbon::parse('2026-11-02 08:00:00'));

        $this->vet = User::factory()->create();

        $this->owner = Owner::factory()->create();

        $this->patient = Patient::factory()->create([
            'owner_id' => $this->owner->getKey(),
        ]);

        $this->scheduler = app(AppointmentScheduler::class);
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_a_veterinarian_cannot_have_overlapping_active_appointments(): void
    {
        $this->bookAt('2026-11-03 09:00:00', '2026-11-03 09:30:00');

        $this->expectException(ValidationException::class);

        $this->bookAt('2026-11-03 09:15:00', '2026-11-03 09:45:00');
    }

    public function test_adjacent_appointments_are_allowed(): void
    {
        $first = $this->bookAt('2026-11-03 09:00:00', '2026-11-03 09:30:00');
        $second = $this->bookAt('2026-11-03 09:30:00', '2026-11-03 10:00:00');

        $this->assertTrue($first->isNot($second));
    }

    public function test_cancelled_appointments_do_not_block_the_slot(): void
    {
        Appointment::factory()->cancelled()->create([
            'patient_id' => $this->patient->getKey(),
            'owner_id' => $this->owner->getKey(),
            'vet_id' => $this->vet->getKey(),
            'starts_at' => '2026-11-03 09:00:00',
            'ends_at' => '2026-11-03 09:30:00',
        ]);

        $appointment = $this->bookAt('2026-11-03 09:00:00', '2026-11-03 09:30:00');

        $this->assertSame(AppointmentStatus::Scheduled, $appointment->status);
    }

    public function test_completed_appointments_do_not_block_the_slot(): void
    {
        Appointment::factory()->completed()->create([
            'patient_id' => $this->patient->getKey(),
            'owner_id' => $this->owner->getKey(),
            'vet_id' => $this->vet->getKey(),
            'starts_at' => '2026-11-03 09:00:00',
            'ends_at' => '2026-11-03 09:30:00',
        ]);

        $appointment = $this->bookAt('2026-11-03 09:00:00', '2026-11-03 09:30:00');

        $this->assertSame(AppointmentStatus::Scheduled, $appointment->status);
    }

    public function test_different_veterinarians_can_have_appointments_at_the_same_time(): void
    {
        $otherVet = User::factory()->create();

        $this->bookAt('2026-11-03 09:00:00', '2026-11-03 09:30:00');

        $appointment = $this->scheduler->book([
            'patient_id' => $this->patient->getKey(),
            'owner_id' => $this->owner->getKey(),
            'vet_id' => $otherVet->getKey(),
            'starts_at' => '2026-11-03 09:00:00',
            'ends_at' => '2026-11-03 09:30:00',
            'type' => AppointmentType::Consultation,
        ]);

        $this->assertSame($otherVet->getKey(), $appointment->vet_id);
    }

    public function test_appointments_outside_clinic_hours_are_rejected(): void
    {
        $this->expectException(ValidationException::class);

        $this->bookAt('2026-11-03 21:00:00', '2026-11-03 21:30:00');
    }

    public function test_appointments_on_closed_days_are_rejected(): void
    {
        $this->expectException(ValidationException::class);

        $this->bookAt('2026-11-08 09:00:00', '2026-11-08 09:30:00');
    }

    public function test_holidays_are_rejected(): void
    {
        Setting::set('holidays', [
            ['date' => '2026-11-03', 'name' => 'Feriado de prueba'],
        ], 'appointments', 'json');

        $this->expectException(ValidationException::class);

        $this->bookAt('2026-11-03 09:00:00', '2026-11-03 09:30:00');
    }

    public function test_veterinarian_time_off_blocks_appointments(): void
    {
        VetTimeOff::factory()->create([
            'user_id' => $this->vet->getKey(),
            'starts_at' => '2026-11-03 09:00:00',
            'ends_at' => '2026-11-03 12:00:00',
        ]);

        $this->expectException(ValidationException::class);

        $this->bookAt('2026-11-03 09:30:00', '2026-11-03 10:00:00');
    }

    public function test_past_appointments_are_rejected(): void
    {
        $this->expectException(ValidationException::class);

        $this->bookAt('2026-11-01 09:00:00', '2026-11-01 09:30:00');
    }

    public function test_rescheduling_an_appointment_ignores_its_own_time_range(): void
    {
        $appointment = $this->bookAt('2026-11-03 09:00:00', '2026-11-03 09:30:00');

        $this->scheduler->reschedule($appointment, [
            'patient_id' => $this->patient->getKey(),
            'owner_id' => $this->owner->getKey(),
            'vet_id' => $this->vet->getKey(),
            'starts_at' => '2026-11-03 09:15:00',
            'ends_at' => '2026-11-03 09:45:00',
            'type' => AppointmentType::FollowUp,
        ]);

        $appointment->refresh();

        $this->assertSame('09:15', $appointment->starts_at->format('H:i'));
        $this->assertSame(AppointmentType::FollowUp, $appointment->type);
    }

    public function test_available_slots_exclude_booked_times(): void
    {
        $this->bookAt('2026-11-03 09:00:00', '2026-11-03 09:30:00');

        $slots = $this->scheduler->availableSlots($this->vet, Carbon::parse('2026-11-03'));

        $this->assertFalse($slots->contains(fn (Carbon $slot): bool => $slot->format('H:i') === '09:00'));
        $this->assertTrue($slots->contains(fn (Carbon $slot): bool => $slot->format('H:i') === '09:30'));
        $this->assertFalse($slots->contains(fn (Carbon $slot): bool => $slot->format('H:i') === '20:00'));
    }

    public function test_a_second_overlapping_booking_is_rejected_after_the_first_succeeds(): void
    {
        $this->bookAt('2026-11-03 09:00:00', '2026-11-03 09:30:00');

        $this->assertSame(1, Appointment::query()->count());

        try {
            $this->bookAt('2026-11-03 09:00:00', '2026-11-03 09:30:00');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('starts_at', $exception->errors());
            $this->assertSame(1, Appointment::query()->count());

            return;
        }

        $this->fail('The second overlapping booking should have been rejected.');
    }

    private function bookAt(string $startsAt, string $endsAt): Appointment
    {
        return $this->scheduler->book([
            'patient_id' => $this->patient->getKey(),
            'owner_id' => $this->owner->getKey(),
            'vet_id' => $this->vet->getKey(),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'type' => AppointmentType::Consultation,
        ]);
    }
}
