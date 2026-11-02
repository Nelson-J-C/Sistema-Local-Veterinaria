<?php

namespace Tests\Feature;

use App\Filament\Resources\Owners\OwnerResource;
use App\Filament\Resources\Owners\Pages\CreateOwner;
use App\Filament\Resources\Owners\Pages\EditOwner;
use App\Filament\Resources\Owners\Pages\ListOwners;
use App\Models\Owner;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class OwnerManagementTest extends TestCase
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

    public function test_administrators_can_open_the_owner_list(): void
    {
        $owner = Owner::factory()->create(['first_name' => 'María', 'last_name' => 'Gómez']);

        $this->get(OwnerResource::getUrl('index'))
            ->assertOk()
            ->assertSee($owner->full_name);
    }

    public function test_users_without_the_permission_cannot_open_the_owner_list(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(OwnerResource::getUrl('index'))
            ->assertForbidden();
    }

    public function test_administrators_can_create_an_owner(): void
    {
        Livewire::test(CreateOwner::class)
            ->fillForm([
                'first_name' => 'María',
                'last_name' => 'Gómez',
                'document_type' => 'ci',
                'document_number' => '1234567',
                'phone' => '70012345',
                'email' => 'maria@example.com',
                'city' => 'Santa Cruz de la Sierra',
                'active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect(OwnerResource::getUrl('index'));

        $owner = Owner::query()->where('email', 'maria@example.com')->first();

        $this->assertNotNull($owner);
        $this->assertSame('María Gómez', $owner->full_name);
        $this->assertSame('1234567', $owner->document_number);
        $this->assertTrue($owner->active);
    }

    public function test_business_owners_can_be_created_without_a_first_name(): void
    {
        Livewire::test(CreateOwner::class)
            ->fillForm([
                'first_name' => null,
                'last_name' => null,
                'business_name' => 'Refugio Patitas Felices',
                'document_type' => 'nit',
                'document_number' => '7654321',
                'phone' => '70098765',
                'active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $owner = Owner::query()->where('business_name', 'Refugio Patitas Felices')->first();

        $this->assertNotNull($owner);
        $this->assertSame('Refugio Patitas Felices', $owner->full_name);
    }

    public function test_document_numbers_must_be_unique_per_document_type(): void
    {
        Owner::factory()->create([
            'document_type' => 'ci',
            'document_number' => '1234567',
        ]);

        Livewire::test(CreateOwner::class)
            ->fillForm([
                'first_name' => 'Otro',
                'last_name' => 'Tutor',
                'document_type' => 'ci',
                'document_number' => '1234567',
                'phone' => '70011111',
                'active' => true,
            ])
            ->call('create')
            ->assertHasFormErrors(['document_number']);
    }

    public function test_owners_can_be_soft_deleted(): void
    {
        $owner = Owner::factory()->create();

        Livewire::test(ListOwners::class)
            ->callAction(TestAction::make('delete')->table($owner))
            ->assertNotified();

        $this->assertSoftDeleted($owner);
    }

    public function test_owner_changes_are_audited(): void
    {
        $owner = Owner::factory()->create(['first_name' => 'Nombre anterior']);

        $owner->update(['first_name' => 'Nombre nuevo']);

        $activity = Activity::query()
            ->where('log_name', 'owner')
            ->where('event', 'updated')
            ->where('subject_id', $owner->getKey())
            ->latest('id')
            ->first();

        $this->assertNotNull($activity);
        $this->assertSame('Nombre nuevo', $activity->attribute_changes['attributes']['first_name'] ?? null);
    }

    public function test_owner_edit_page_renders(): void
    {
        $owner = Owner::factory()->create();

        $this->get(OwnerResource::getUrl('edit', ['record' => $owner]))
            ->assertOk();

        Livewire::test(EditOwner::class, ['record' => $owner->getRouteKey()])
            ->assertOk()
            ->assertFormSet(['first_name' => $owner->first_name]);
    }
}
