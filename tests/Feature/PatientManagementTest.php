<?php

namespace Tests\Feature;

use App\Enums\AlertSeverity;
use App\Enums\PatientAlertType;
use App\Enums\Sex;
use App\Filament\Resources\Patients\Pages\CreatePatient;
use App\Filament\Resources\Patients\Pages\EditPatient;
use App\Filament\Resources\Patients\Pages\ListPatients;
use App\Filament\Resources\Patients\PatientResource;
use App\Filament\Resources\Patients\RelationManagers\AlertsRelationManager;
use App\Models\Breed;
use App\Models\Owner;
use App\Models\Patient;
use App\Models\PatientAlert;
use App\Models\Species;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\CreateAction;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PatientManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        Filament::setCurrentPanel('admin');

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->actingAs($this->admin);
    }

    public function test_administrators_can_open_the_patient_list(): void
    {
        $patient = Patient::factory()->create(['name' => 'Firulais']);

        $this->get(PatientResource::getUrl('index'))
            ->assertOk()
            ->assertSee('Firulais');
    }

    public function test_users_without_the_permission_cannot_open_the_patient_list(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(PatientResource::getUrl('index'))
            ->assertForbidden();
    }

    public function test_administrators_can_create_a_patient_with_species_and_breed(): void
    {
        $owner = Owner::factory()->create();
        $species = Species::factory()->create();
        $breed = Breed::factory()->create(['species_id' => $species->getKey()]);

        Livewire::test(CreatePatient::class)
            ->fillForm([
                'owner_id' => $owner->getKey(),
                'name' => 'Firulais',
                'species_id' => $species->getKey(),
                'breed_id' => $breed->getKey(),
                'sex' => Sex::Male->value,
                'birth_date' => '2022-03-15',
                'weight_kg' => 12.5,
                'sterilized' => false,
                'active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect(PatientResource::getUrl('index'));

        $patient = Patient::query()->where('name', 'Firulais')->first();

        $this->assertNotNull($patient);
        $this->assertTrue($patient->owner->is($owner));
        $this->assertTrue($patient->species->is($species));
        $this->assertTrue($patient->breed->is($breed));
        $this->assertSame(Sex::Male, $patient->sex);
        $this->assertSame('12.50', $patient->weight_kg);
    }

    public function test_a_breed_must_belong_to_the_selected_species(): void
    {
        $owner = Owner::factory()->create();
        $species = Species::factory()->create();
        $otherBreed = Breed::factory()->create();

        Livewire::test(CreatePatient::class)
            ->fillForm([
                'owner_id' => $owner->getKey(),
                'name' => 'Michi',
                'species_id' => $species->getKey(),
                'breed_id' => $otherBreed->getKey(),
                'sex' => Sex::Female->value,
                'active' => true,
            ])
            ->call('create')
            ->assertHasFormErrors(['breed_id']);

        $this->assertDatabaseMissing('patients', ['name' => 'Michi']);
    }

    public function test_microchip_numbers_must_be_unique(): void
    {
        $existing = Patient::factory()->create(['microchip' => 'CHIP-001']);
        $owner = Owner::factory()->create();
        $species = Species::factory()->create();

        Livewire::test(CreatePatient::class)
            ->fillForm([
                'owner_id' => $owner->getKey(),
                'name' => 'Otro paciente',
                'species_id' => $species->getKey(),
                'sex' => Sex::Unknown->value,
                'microchip' => $existing->microchip,
                'active' => true,
            ])
            ->call('create')
            ->assertHasFormErrors(['microchip']);
    }

    public function test_patients_can_be_soft_deleted(): void
    {
        $patient = Patient::factory()->create();

        Livewire::test(ListPatients::class)
            ->callAction(TestAction::make('delete')->table($patient))
            ->assertNotified();

        $this->assertSoftDeleted($patient);
    }

    public function test_patient_alerts_are_listed_in_the_patient_record(): void
    {
        $patient = Patient::factory()->create();
        $alerts = PatientAlert::factory()->count(2)->create(['patient_id' => $patient->getKey()]);

        Livewire::test(AlertsRelationManager::class, [
            'ownerRecord' => $patient,
            'pageClass' => EditPatient::class,
        ])
            ->assertOk()
            ->assertCanSeeTableRecords($alerts);
    }

    public function test_administrators_can_create_a_patient_alert(): void
    {
        $patient = Patient::factory()->create();

        Livewire::test(AlertsRelationManager::class, [
            'ownerRecord' => $patient,
            'pageClass' => EditPatient::class,
        ])
            ->callAction(TestAction::make(CreateAction::class)->table(), [
                'type' => PatientAlertType::Allergy->value,
                'title' => 'Alergia a la penicilina',
                'severity' => AlertSeverity::High->value,
                'active' => true,
            ])
            ->assertNotified();

        $this->assertDatabaseHas('patient_alerts', [
            'patient_id' => $patient->getKey(),
            'title' => 'Alergia a la penicilina',
            'created_by' => $this->admin->getKey(),
        ]);
    }

    public function test_the_patient_record_page_renders(): void
    {
        $patient = Patient::factory()->create();

        $this->get(PatientResource::getUrl('view', ['record' => $patient]))
            ->assertOk()
            ->assertSee($patient->name);
    }
}
