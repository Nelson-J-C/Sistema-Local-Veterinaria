<?php

namespace Tests\Feature;

use App\Enums\ReminderStatus;
use App\Enums\ReminderType;
use App\Filament\Resources\Consultations\Pages\ViewConsultation;
use App\Filament\Resources\Consultations\RelationManagers\VaccinationsRelationManager as ConsultationVaccinationsRelationManager;
use App\Filament\Resources\Dewormings\Pages\CreateDeworming;
use App\Filament\Resources\Dewormings\Pages\ListDewormings;
use App\Filament\Resources\Patients\Pages\ViewPatient;
use App\Filament\Resources\Patients\RelationManagers\DewormingsRelationManager;
use App\Filament\Resources\Patients\RelationManagers\VaccinationsRelationManager;
use App\Filament\Resources\Reminders\Pages\ListReminders;
use App\Filament\Resources\Vaccinations\Pages\CreateVaccination;
use App\Filament\Resources\Vaccinations\Pages\EditVaccination;
use App\Filament\Resources\Vaccinations\Pages\ListVaccinations;
use App\Filament\Resources\Vaccines\Pages\CreateVaccine;
use App\Filament\Widgets\PreventiveReminderWidget;
use App\Models\Consultation;
use App\Models\Deworming;
use App\Models\Owner;
use App\Models\Patient;
use App\Models\Reminder;
use App\Models\User;
use App\Models\Vaccination;
use App\Models\Vaccine;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\CreateAction;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PreventiveCareTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $vet;

    private Owner $owner;

    private Patient $patient;

    private Vaccine $vaccine;

    protected function setUp(): void
    {
        parent::setUp();

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

        $this->vaccine = Vaccine::factory()->create([
            'species_id' => $this->patient->species_id,
            'name' => 'Rabia',
            'default_revaccination_days' => 365,
        ]);

        $this->actingAs($this->admin);
    }

    public function test_administrators_can_open_the_vaccination_list(): void
    {
        $vaccination = $this->vaccination();

        $this->get(ListVaccinations::getUrl())
            ->assertOk()
            ->assertSee($this->patient->name)
            ->assertSee($vaccination->vaccine->name);
    }

    public function test_reception_users_cannot_open_preventive_care(): void
    {
        $reception = User::factory()->create();
        $reception->assignRole('recepcion');

        $this->actingAs($reception)
            ->get(ListVaccinations::getUrl())
            ->assertForbidden();

        $this->actingAs($reception)
            ->get(ListDewormings::getUrl())
            ->assertForbidden();
    }

    public function test_a_vaccination_calculates_the_next_dose_from_the_catalog(): void
    {
        Livewire::test(CreateVaccination::class)
            ->fillForm([
                'patient_id' => $this->patient->getKey(),
                'vaccine_id' => $this->vaccine->getKey(),
                'vet_id' => $this->vet->getKey(),
                'administered_at' => '2026-11-04 09:00:00',
                'batch_number' => 'LOTE-001',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $vaccination = Vaccination::query()->firstOrFail();

        $this->assertSame('2027-11-04 09:00:00', $vaccination->next_due_at->format('Y-m-d H:i:s'));
        $this->assertSame($this->admin->getKey(), $vaccination->created_by);

        $this->assertDatabaseHas('reminders', [
            'reminderable_type' => Vaccination::class,
            'reminderable_id' => $vaccination->getKey(),
            'type' => ReminderType::Vaccination->value,
            'status' => ReminderStatus::Pending->value,
            'owner_id' => $this->owner->getKey(),
        ]);
    }

    public function test_editing_the_next_dose_updates_the_pending_reminder(): void
    {
        $vaccination = $this->vaccination(['next_due_at' => now()->addDays(30)]);

        $this->assertDatabaseCount('reminders', 1);

        Livewire::test(EditVaccination::class, ['record' => $vaccination->getKey()])
            ->fillForm([
                'next_due_at' => '2027-06-15 10:00:00',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseCount('reminders', 1);
        $this->assertDatabaseHas('reminders', [
            'id' => $vaccination->reminders()->firstOrFail()->getKey(),
            'due_at' => '2027-06-15 10:00:00',
            'status' => ReminderStatus::Pending->value,
        ]);
    }

    public function test_dewormings_generate_a_pending_reminder(): void
    {
        Livewire::test(CreateDeworming::class)
            ->fillForm([
                'patient_id' => $this->patient->getKey(),
                'product_name' => 'Albendazol',
                'dose' => '1 tableta',
                'vet_id' => $this->vet->getKey(),
                'administered_at' => '2026-11-04 10:00:00',
                'next_due_at' => '2027-02-04 10:00:00',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $deworming = Deworming::query()->firstOrFail();

        $this->assertSame($this->patient->getKey(), $deworming->patient_id);
        $this->assertSame($this->admin->getKey(), $deworming->created_by);

        $this->assertDatabaseHas('reminders', [
            'reminderable_type' => Deworming::class,
            'reminderable_id' => $deworming->getKey(),
            'type' => ReminderType::Deworming->value,
            'status' => ReminderStatus::Pending->value,
            'notes' => 'Desparasitación: Albendazol',
        ]);
    }

    public function test_a_vaccination_can_be_recorded_from_the_patient_chart(): void
    {
        Livewire::test(VaccinationsRelationManager::class, [
            'ownerRecord' => $this->patient,
            'pageClass' => ViewPatient::class,
        ])
            ->callAction(TestAction::make(CreateAction::class)->table(), [
                'vaccine_id' => $this->vaccine->getKey(),
                'vet_id' => $this->vet->getKey(),
                'administered_at' => '2026-11-04 11:00:00',
            ])
            ->assertNotified();

        $this->assertDatabaseHas('vaccinations', [
            'patient_id' => $this->patient->getKey(),
            'vaccine_id' => $this->vaccine->getKey(),
            'created_by' => $this->admin->getKey(),
        ]);
    }

    public function test_a_deworming_can_be_recorded_from_the_patient_chart(): void
    {
        Livewire::test(DewormingsRelationManager::class, [
            'ownerRecord' => $this->patient,
            'pageClass' => ViewPatient::class,
        ])
            ->callAction(TestAction::make(CreateAction::class)->table(), [
                'product_name' => 'Praziquantel',
                'administered_at' => '2026-11-04 11:30:00',
                'next_due_at' => '2027-02-04 11:30:00',
            ])
            ->assertNotified();

        $this->assertDatabaseHas('dewormings', [
            'patient_id' => $this->patient->getKey(),
            'product_name' => 'Praziquantel',
            'created_by' => $this->admin->getKey(),
        ]);
    }

    public function test_a_vaccination_can_be_linked_to_a_consultation(): void
    {
        $consultation = Consultation::factory()->create([
            'patient_id' => $this->patient->getKey(),
            'vet_id' => $this->vet->getKey(),
            'created_by' => $this->admin->getKey(),
        ]);

        Livewire::test(ConsultationVaccinationsRelationManager::class, [
            'ownerRecord' => $consultation,
            'pageClass' => ViewConsultation::class,
        ])
            ->callAction(TestAction::make(CreateAction::class)->table(), [
                'vaccine_id' => $this->vaccine->getKey(),
                'vet_id' => $this->vet->getKey(),
                'administered_at' => '2026-11-04 12:00:00',
            ])
            ->assertNotified();

        $this->assertDatabaseHas('vaccinations', [
            'patient_id' => $this->patient->getKey(),
            'consultation_id' => $consultation->getKey(),
        ]);
    }

    public function test_the_vaccine_card_is_printable(): void
    {
        $this->vaccination();
        Deworming::factory()->create([
            'patient_id' => $this->patient->getKey(),
            'product_name' => 'Praziquantel',
        ]);

        $this->get(route('patients.vaccine-card', $this->patient))
            ->assertOk()
            ->assertSee('Tarjeta de vacunas')
            ->assertSee($this->patient->name)
            ->assertSee($this->owner->full_name)
            ->assertSee('Rabia')
            ->assertSee('Praziquantel')
            ->assertSee('Imprimir tarjeta');
    }

    public function test_users_without_vaccination_permission_cannot_print_the_card(): void
    {
        $reception = User::factory()->create();
        $reception->assignRole('recepcion');

        $this->actingAs($reception)
            ->get(route('patients.vaccine-card', $this->patient))
            ->assertForbidden();
    }

    public function test_administrators_can_add_vaccines_to_the_catalog(): void
    {
        Livewire::test(CreateVaccine::class)
            ->fillForm([
                'species_id' => $this->patient->species_id,
                'name' => 'Leucemia felina',
                'default_revaccination_days' => 365,
                'active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('vaccines', [
            'name' => 'Leucemia felina',
            'default_revaccination_days' => 365,
            'active' => 1,
        ]);
    }

    public function test_pending_reminders_can_be_marked_as_sent(): void
    {
        $vaccination = $this->vaccination(['next_due_at' => now()->subDay()]);
        $reminder = Reminder::query()->firstOrFail();

        Livewire::test(ListReminders::class)
            ->callAction(TestAction::make('mark_sent')->table($reminder))
            ->assertNotified();

        $reminder->refresh();

        $this->assertSame(ReminderStatus::Sent, $reminder->status);
        $this->assertNotNull($reminder->sent_at);
        $this->assertSame(ReminderType::Vaccination, $reminder->type);
        $this->assertTrue($vaccination->isOverdue());
    }

    public function test_due_preventive_reminders_appear_on_the_dashboard_widget(): void
    {
        $this->vaccination(['next_due_at' => now()->subDay()]);
        $reminder = Reminder::query()->firstOrFail();

        Livewire::test(PreventiveReminderWidget::class)
            ->assertCanSeeTableRecords([$reminder]);
    }

    /**
     * Create a vaccination for the default patient.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function vaccination(array $attributes = []): Vaccination
    {
        return Vaccination::factory()->create([
            'patient_id' => $this->patient->getKey(),
            'vaccine_id' => $this->vaccine->getKey(),
            'vet_id' => $this->vet->getKey(),
            'created_by' => $this->admin->getKey(),
            ...$attributes,
        ]);
    }
}
