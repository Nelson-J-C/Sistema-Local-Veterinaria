<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Enums\ConsultationStatus;
use App\Filament\Resources\Appointments\Pages\ListAppointments;
use App\Filament\Resources\Consultations\ConsultationResource;
use App\Filament\Resources\Consultations\Pages\CreateConsultation;
use App\Filament\Resources\Consultations\Pages\EditConsultation;
use App\Filament\Resources\Consultations\Pages\ListConsultations;
use App\Filament\Resources\Consultations\Pages\ViewConsultation;
use App\Filament\Resources\Consultations\RelationManagers\AddendaRelationManager;
use App\Filament\Resources\Consultations\RelationManagers\AttachmentsRelationManager;
use App\Filament\Resources\Consultations\RelationManagers\DiagnosesRelationManager;
use App\Filament\Resources\Consultations\RelationManagers\MeasurementsRelationManager;
use App\Filament\Resources\Consultations\RelationManagers\PrescriptionsRelationManager;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Owner;
use App\Models\Patient;
use App\Models\User;
use App\Services\ConsultationWorkflow;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\CreateAction;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class ConsultationManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $vet;

    private Owner $owner;

    private Patient $patient;

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

        $this->actingAs($this->admin);
    }

    public function test_administrators_can_open_the_consultation_list(): void
    {
        $consultation = $this->consultation();

        $this->get(ConsultationResource::getUrl('index'))
            ->assertOk()
            ->assertSee($consultation->patient->name);
    }

    public function test_users_without_the_permission_cannot_open_consultations(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(ConsultationResource::getUrl('index'))
            ->assertForbidden();
    }

    public function test_laboratory_users_can_view_but_not_create_consultations(): void
    {
        $laboratory = User::factory()->create();
        $laboratory->assignRole('laboratorio');

        $this->actingAs($laboratory)
            ->get(ConsultationResource::getUrl('index'))
            ->assertOk();

        $this->actingAs($laboratory)
            ->get(ConsultationResource::getUrl('create'))
            ->assertForbidden();
    }

    public function test_administrators_can_create_a_draft_consultation(): void
    {
        Livewire::test(CreateConsultation::class)
            ->fillForm([
                'patient_id' => $this->patient->getKey(),
                'vet_id' => $this->vet->getKey(),
                'consulted_at' => '2026-11-03 09:00:00',
                'chief_complaint' => 'Decaimiento y falta de apetito',
                'subjective' => 'El tutor refiere dos días de inapetencia.',
                'objective' => 'Temperatura 39.1 °C, mucosas pálidas.',
                'assessment' => 'Sospecha de proceso infeccioso.',
                'plan' => 'Hemograma y tratamiento sintomático.',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $consultation = Consultation::query()->firstOrFail();

        $this->assertSame($this->patient->getKey(), $consultation->patient_id);
        $this->assertSame($this->vet->getKey(), $consultation->vet_id);
        $this->assertSame(ConsultationStatus::Draft, $consultation->status);
        $this->assertSame($this->admin->getKey(), $consultation->created_by);
        $this->assertNull($consultation->appointment_id);
    }

    public function test_a_consultation_can_be_started_from_an_appointment(): void
    {
        $appointment = $this->appointment();

        Livewire::test(ListAppointments::class)
            ->callAction(TestAction::make('start_consultation')->table($appointment))
            ->assertNotified();

        $consultation = Consultation::query()->firstOrFail();

        $this->assertSame($appointment->getKey(), $consultation->appointment_id);
        $this->assertSame($this->patient->getKey(), $consultation->patient_id);
        $this->assertSame($this->vet->getKey(), $consultation->vet_id);
        $this->assertSame(AppointmentStatus::InProgress, $appointment->fresh()->status);
    }

    public function test_finalizing_a_consultation_locks_it_and_completes_the_appointment(): void
    {
        $appointment = $this->appointment();
        $consultation = $this->consultation([
            'appointment_id' => $appointment->getKey(),
        ]);

        $consultation->measurements()->create([
            'patient_id' => $this->patient->getKey(),
            'measured_at' => now(),
            'weight_kg' => 23.4,
            'recorded_by' => $this->vet->getKey(),
        ]);

        Livewire::test(ListConsultations::class)
            ->callAction(TestAction::make('finalize')->table($consultation))
            ->assertNotified();

        $consultation->refresh();

        $this->assertSame(ConsultationStatus::Finalized, $consultation->status);
        $this->assertSame($this->admin->getKey(), $consultation->finalized_by);
        $this->assertNotNull($consultation->finalized_at);
        $this->assertSame(AppointmentStatus::Completed, $appointment->fresh()->status);
        $this->assertSame('23.40', $this->patient->fresh()->weight_kg);
    }

    public function test_a_finalized_consultation_cannot_be_edited(): void
    {
        $consultation = $this->consultation([
            'status' => ConsultationStatus::Finalized,
            'finalized_at' => now(),
            'finalized_by' => $this->vet->getKey(),
        ]);

        $this->get(ConsultationResource::getUrl('edit', ['record' => $consultation]))
            ->assertForbidden();
    }

    public function test_an_addendum_can_be_added_to_a_finalized_consultation(): void
    {
        $consultation = $this->consultation([
            'status' => ConsultationStatus::Finalized,
            'finalized_at' => now(),
            'finalized_by' => $this->vet->getKey(),
        ]);

        Livewire::test(ListConsultations::class)
            ->callAction(TestAction::make('addendum')->table($consultation), [
                'content' => 'Se corrige la dosis indicada en la receta.',
            ])
            ->assertNotified();

        $this->assertDatabaseHas('consultation_addenda', [
            'consultation_id' => $consultation->getKey(),
            'content' => 'Se corrige la dosis indicada en la receta.',
            'created_by' => $this->admin->getKey(),
        ]);

        $this->assertSame(ConsultationStatus::Amended, $consultation->fresh()->status);
    }

    public function test_an_addendum_cannot_be_added_to_a_draft_consultation(): void
    {
        $consultation = $this->consultation();

        $this->expectException(ValidationException::class);

        app(ConsultationWorkflow::class)->addendum($consultation, 'Corrección', $this->vet->getKey());
    }

    public function test_measurements_can_be_recorded_on_a_draft_consultation(): void
    {
        $consultation = $this->consultation();

        Livewire::test(MeasurementsRelationManager::class, [
            'ownerRecord' => $consultation,
            'pageClass' => EditConsultation::class,
        ])
            ->callAction(TestAction::make(CreateAction::class)->table(), [
                'measured_at' => '2026-11-03 09:15:00',
                'weight_kg' => 23.4,
                'temperature_c' => 39.1,
                'heart_rate' => 120,
                'respiratory_rate' => 32,
                'body_condition_score' => 5,
                'pain_score' => 1,
            ])
            ->assertNotified();

        $this->assertDatabaseHas('patient_measurements', [
            'consultation_id' => $consultation->getKey(),
            'patient_id' => $this->patient->getKey(),
            'recorded_by' => $this->admin->getKey(),
            'heart_rate' => 120,
        ]);
    }

    public function test_diagnoses_can_be_recorded_on_a_draft_consultation(): void
    {
        $consultation = $this->consultation();

        Livewire::test(DiagnosesRelationManager::class, [
            'ownerRecord' => $consultation,
            'pageClass' => EditConsultation::class,
        ])
            ->callAction(TestAction::make(CreateAction::class)->table(), [
                'name' => 'Gastroenteritis',
                'code' => 'GI-001',
                'type' => 'primary',
                'notes' => 'Cuadro agudo con deshidratación leve.',
            ])
            ->assertNotified();

        $this->assertDatabaseHas('consultation_diagnoses', [
            'consultation_id' => $consultation->getKey(),
            'name' => 'Gastroenteritis',
            'type' => 'primary',
        ]);
    }

    public function test_prescriptions_can_be_recorded_on_a_draft_consultation(): void
    {
        $consultation = $this->consultation();

        Livewire::test(PrescriptionsRelationManager::class, [
            'ownerRecord' => $consultation,
            'pageClass' => EditConsultation::class,
        ])
            ->callAction(TestAction::make(CreateAction::class)->table(), [
                'medication_name' => 'Amoxicilina',
                'presentation' => 'Suspensión 250 mg/5 ml',
                'dose' => '1 ml',
                'frequency' => 'Cada 12 horas',
                'route' => 'oral',
                'duration_days' => 7,
                'quantity' => 1,
                'instructions' => 'Administrar con alimento.',
            ])
            ->assertNotified();

        $this->assertDatabaseHas('prescriptions', [
            'consultation_id' => $consultation->getKey(),
            'medication_name' => 'Amoxicilina',
            'route' => 'oral',
            'duration_days' => 7,
        ]);
    }

    public function test_addenda_can_be_created_from_the_consultation_view(): void
    {
        $consultation = $this->consultation([
            'status' => ConsultationStatus::Finalized,
            'finalized_at' => now(),
            'finalized_by' => $this->vet->getKey(),
        ]);

        Livewire::test(AddendaRelationManager::class, [
            'ownerRecord' => $consultation,
            'pageClass' => ViewConsultation::class,
        ])
            ->callAction(TestAction::make(CreateAction::class)->table(), [
                'content' => 'El tutor confirma que el paciente mejoró al segundo día.',
            ])
            ->assertNotified();

        $this->assertDatabaseHas('consultation_addenda', [
            'consultation_id' => $consultation->getKey(),
            'content' => 'El tutor confirma que el paciente mejoró al segundo día.',
            'created_by' => $this->admin->getKey(),
        ]);

        $this->assertSame(ConsultationStatus::Amended, $consultation->fresh()->status);
    }

    public function test_files_can_be_attached_to_a_consultation(): void
    {
        Storage::fake('local');

        $consultation = $this->consultation();

        Livewire::test(AttachmentsRelationManager::class, [
            'ownerRecord' => $consultation,
            'pageClass' => ViewConsultation::class,
        ])
            ->callAction(TestAction::make(CreateAction::class)->table(), [
                'path' => UploadedFile::fake()->create('resultado.pdf', 200, 'application/pdf'),
                'notes' => 'Resultado de laboratorio externo.',
            ])
            ->assertNotified();

        $attachment = $consultation->attachments()->firstOrFail();

        $this->assertSame('resultado.pdf', $attachment->original_name);
        $this->assertSame($this->patient->getKey(), $attachment->patient_id);
        $this->assertSame($this->admin->getKey(), $attachment->uploaded_by);
        Storage::disk('local')->assertExists($attachment->path);
    }

    /**
     * Create a draft consultation for the default patient.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function consultation(array $attributes = []): Consultation
    {
        return Consultation::factory()->create([
            'patient_id' => $this->patient->getKey(),
            'vet_id' => $this->vet->getKey(),
            'created_by' => $this->admin->getKey(),
            ...$attributes,
        ]);
    }

    /**
     * Create a scheduled appointment for the default patient.
     */
    private function appointment(): Appointment
    {
        return Appointment::factory()->create([
            'patient_id' => $this->patient->getKey(),
            'owner_id' => $this->owner->getKey(),
            'vet_id' => $this->vet->getKey(),
            'starts_at' => now()->addHour(),
            'ends_at' => now()->addHour()->addMinutes(30),
            'type' => AppointmentType::Consultation,
            'status' => AppointmentStatus::Scheduled,
        ]);
    }
}
