<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementTest extends TestCase
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

    public function test_administrators_can_open_the_user_list(): void
    {
        $this->get(UserResource::getUrl('index'))
            ->assertOk()
            ->assertSee($this->admin->name);
    }

    public function test_users_without_the_permission_cannot_open_the_user_list(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(UserResource::getUrl('index'))
            ->assertForbidden();
    }

    public function test_administrators_can_create_a_user_with_a_role(): void
    {
        $role = Role::findByName('veterinario');

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Dra. Ana Pérez',
                'email' => 'ana@veterinaria.local',
                'phone' => '70000000',
                'password' => 'Secret12345!',
                'active' => true,
                'roles' => [$role->getKey()],
                'specialty' => 'Medicina interna',
                'default_appointment_duration' => 30,
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect(UserResource::getUrl('index'));

        $user = User::query()->where('email', 'ana@veterinaria.local')->first();

        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('veterinario'));
        $this->assertTrue($user->active);
        $this->assertTrue(Hash::check('Secret12345!', $user->password));
        $this->assertNotSame('Secret12345!', $user->password);
    }

    public function test_the_password_is_optional_when_editing_a_user(): void
    {
        $user = User::factory()->create([
            'name' => 'Nombre anterior',
            'password' => 'Secret12345!',
        ]);
        $user->assignRole('recepcion');

        $originalPassword = $user->password;

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->fillForm([
                'name' => 'Nombre nuevo',
                'password' => '',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();

        $this->assertSame('Nombre nuevo', $user->name);
        $this->assertSame($originalPassword, $user->password);
        $this->assertTrue(Hash::check('Secret12345!', $user->password));
    }

    public function test_administrators_cannot_delete_themselves(): void
    {
        $this->assertFalse(Gate::forUser($this->admin)->allows('delete', $this->admin));
        $this->assertFalse(Gate::forUser($this->admin)->allows('forceDelete', $this->admin));
    }

    public function test_administrators_can_delete_another_user(): void
    {
        $target = User::factory()->create();
        $target->assignRole('recepcion');

        $this->assertTrue(Gate::forUser($this->admin)->allows('delete', $target));

        Livewire::test(ListUsers::class)
            ->callTableAction('delete', $target)
            ->assertNotified();

        $this->assertSoftDeleted($target);
    }

    public function test_a_non_admin_cannot_delete_the_last_administrator(): void
    {
        $operator = User::factory()->create();
        $operator->givePermissionTo('users.delete');

        $this->assertFalse(Gate::forUser($operator)->allows('delete', $this->admin));
    }

    public function test_clients_cannot_access_the_administration_panel(): void
    {
        $client = User::factory()->create();
        $client->assignRole('cliente');

        $this->actingAs($client)
            ->get('/admin')
            ->assertForbidden();
    }
}
