<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Filament\Pages\Agenda;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Resources\Appointments\Pages\CreateAppointment;
use App\Filament\Resources\Appointments\Pages\ListAppointments;
use App\Filament\Resources\VetTimeOffs\Pages\CreateVetTimeOff;
use App\Filament\Resources\VetTimeOffs\VetTimeOffResource;
use App\Models\Appointment;
use App\Models\Owner;
use App\Models\Patient;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class AppointmentManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $vet;

    private Owner $owner;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-11-02 08:00:00'));

        $this->seed(RolesAndPermissionsSeeder::class);

        Filament::setCurrentPanel('admin');

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->vet = User::factory()->create(['name' => 'Dra. Ana Pérez']);
        $this->vet->assignRole('veterinario');

        $this->owner = Owner::factory()->create();

        $this->patient = Patient::factory()->create([
            'owner_id' => $this->owner->getKey(),
            'name' => 'Firulais',
        ]);

        $this->actingAs($this->admin);
    }

    protected function tearDown(): void
    {
        $this->travelBack();

        parent::tearDown();
    }

    public function test_administrators_can_open_the_appointment_list(): void
    {
        $appointment = $this->appointment();

        $this->get(AppointmentResource::getUrl('index'))
            ->assertOk()
            ->assertSee($appointment->patient->name);
    }

    public function test_users_without_the_appointment_permission_cannot_open_the_list(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(AppointmentResource::getUrl('index'))
            ->assertForbidden();
    }

    public function test_administrators_can_create_an_appointment(): void
    {
        Livewire::test(CreateAppointment::class)
            ->fillForm([
                'patient_id' => $this->patient->getKey(),
                'vet_id' => $this->vet->getKey(),
                'type' => AppointmentType::Consultation->value,
                'starts_at' => '2026-11-03 09:00:00',
                'ends_at' => '2026-11-03 09:30:00',
                'reason' => 'Control general',
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect(AppointmentResource::getUrl('index'));

        $appointment = Appointment::query()->firstOrFail();

        $this->assertSame($this->patient->getKey(), $appointment->patient_id);
        $this->assertSame($this->owner->getKey(), $appointment->owner_id);
        $this->assertSame($this->vet->getKey(), $appointment->vet_id);
        $this->assertSame(AppointmentStatus::Scheduled, $appointment->status);
        $this->assertSame($this->admin->getKey(), $appointment->created_by);
    }

    public function test_the_form_rejects_an_overlapping_appointment(): void
    {
        $this->appointment([
            'starts_at' => '2026-11-03 09:00:00',
            'ends_at' => '2026-11-03 09:30:00',
        ]);

        Livewire::test(CreateAppointment::class)
            ->fillForm([
                'patient_id' => $this->patient->getKey(),
                'vet_id' => $this->vet->getKey(),
                'type' => AppointmentType::Consultation->value,
                'starts_at' => '2026-11-03 09:15:00',
                'ends_at' => '2026-11-03 09:45:00',
            ])
            ->call('create')
            ->assertHasFormErrors(['starts_at']);

        $this->assertSame(1, Appointment::query()->count());
    }

    public function test_an_appointment_can_be_confirmed_from_the_list(): void
    {
        $appointment = $this->appointment();

        Livewire::test(ListAppointments::class)
            ->callAction(TestAction::make('confirm')->table($appointment))
            ->assertNotified();

        $this->assertSame(AppointmentStatus::Confirmed, $appointment->fresh()->status);
    }

    public function test_an_appointment_can_be_cancelled_with_a_reason(): void
    {
        $appointment = $this->appointment();

        Livewire::test(ListAppointments::class)
            ->callAction(TestAction::make('cancel')->table($appointment), [
                'cancellation_reason' => 'El tutor no puede asistir',
            ])
            ->assertNotified();

        $appointment->refresh();

        $this->assertSame(AppointmentStatus::Cancelled, $appointment->status);
        $this->assertSame('El tutor no puede asistir', $appointment->cancellation_reason);
        $this->assertNotNull($appointment->cancelled_at);
        $this->assertSame($this->admin->getKey(), $appointment->cancelled_by);
    }

    public function test_a_reminder_can_be_marked_as_sent(): void
    {
        $appointment = $this->appointment([
            'starts_at' => '2026-11-03 10:00:00',
            'ends_at' => '2026-11-03 10:30:00',
        ]);

        Livewire::test(ListAppointments::class)
            ->callAction(TestAction::make('mark_reminder')->table($appointment))
            ->assertNotified();

        $this->assertNotNull($appointment->fresh()->reminder_sent_at);
    }

    public function test_the_agenda_page_renders_the_daily_appointments(): void
    {
        $this->appointment([
            'starts_at' => '2026-11-02 09:00:00',
            'ends_at' => '2026-11-02 09:30:00',
        ]);

        $this->get(Agenda::getUrl())
            ->assertOk()
            ->assertSee('Firulais')
            ->assertSee('Dra. Ana Pérez');
    }

    public function test_the_agenda_page_can_switch_to_the_weekly_view(): void
    {
        $this->appointment([
            'starts_at' => '2026-11-04 09:00:00',
            'ends_at' => '2026-11-04 09:30:00',
        ]);

        Livewire::test(Agenda::class)
            ->set('viewMode', 'week')
            ->assertOk()
            ->assertSee('Firulais');
    }

    public function test_administrators_can_create_a_veterinarian_time_off_block(): void
    {
        Livewire::test(CreateVetTimeOff::class)
            ->fillForm([
                'user_id' => $this->vet->getKey(),
                'starts_at' => '2026-11-04 09:00:00',
                'ends_at' => '2026-11-04 12:00:00',
                'reason' => 'Curso de actualización',
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect(VetTimeOffResource::getUrl('index'));

        $this->assertDatabaseHas('vet_time_offs', [
            'user_id' => $this->vet->getKey(),
            'reason' => 'Curso de actualización',
            'created_by' => $this->admin->getKey(),
        ]);
    }

    public function test_a_time_off_block_cannot_overlap_an_active_appointment(): void
    {
        $this->appointment([
            'starts_at' => '2026-11-04 09:00:00',
            'ends_at' => '2026-11-04 09:30:00',
        ]);

        Livewire::test(CreateVetTimeOff::class)
            ->fillForm([
                'user_id' => $this->vet->getKey(),
                'starts_at' => '2026-11-04 09:00:00',
                'ends_at' => '2026-11-04 12:00:00',
                'reason' => 'Vacaciones',
            ])
            ->call('create')
            ->assertHasFormErrors(['starts_at']);

        $this->assertDatabaseMissing('vet_time_offs', [
            'user_id' => $this->vet->getKey(),
            'reason' => 'Vacaciones',
        ]);
    }

    public function test_users_without_the_appointment_permission_cannot_create_time_off_blocks(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(VetTimeOffResource::getUrl('create'))
            ->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function appointment(array $attributes = []): Appointment
    {
        return Appointment::factory()->create([
            'patient_id' => $this->patient->getKey(),
            'owner_id' => $this->owner->getKey(),
            'vet_id' => $this->vet->getKey(),
            'starts_at' => '2026-11-03 09:00:00',
            'ends_at' => '2026-11-03 09:30:00',
            ...$attributes,
        ]);
    }
}
