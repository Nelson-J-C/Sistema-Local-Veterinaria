<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class RolesAndPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_the_expected_roles_and_permissions_are_seeded(): void
    {
        $this->assertDatabaseHas('roles', ['name' => 'admin']);
        $this->assertDatabaseHas('roles', ['name' => 'veterinario']);
        $this->assertDatabaseHas('roles', ['name' => 'recepcion']);
        $this->assertDatabaseHas('permissions', ['name' => 'users.view']);
        $this->assertDatabaseHas('permissions', ['name' => 'invoices.void']);
    }

    public function test_the_admin_role_receives_every_permission(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->assertTrue($admin->hasPermissionTo('users.delete'));
        $this->assertTrue($admin->hasPermissionTo('invoices.void'));
    }

    public function test_administrators_bypass_gate_checks(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin);

        $this->assertTrue(Gate::allows('settings.update'));
    }

    public function test_users_without_the_permission_are_denied_by_default(): void
    {
        $veterinarian = User::factory()->create();
        $veterinarian->assignRole('veterinario');

        $this->actingAs($veterinarian);

        $this->assertFalse(Gate::allows('invoices.void'));
    }
}
