<?php

namespace Tests\Feature;

use App\Filament\Pages\ManageClinicSettings;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SettingsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClinicSettingsPageTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RolesAndPermissionsSeeder::class,
            SettingsSeeder::class,
        ]);

        Filament::setCurrentPanel('admin');

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->actingAs($this->admin);
    }

    public function test_administrators_can_open_the_clinic_settings_page(): void
    {
        $this->get(ManageClinicSettings::getUrl())
            ->assertOk()
            ->assertSee('Configuración de la clínica');
    }

    public function test_administrators_can_update_the_clinic_settings(): void
    {
        Livewire::test(ManageClinicSettings::class)
            ->fillForm([
                'clinic_name' => 'Clínica Veterinaria San Roque',
                'clinic_phone' => '33445566',
                'clinic_email' => 'contacto@sanroque.local',
                'tax_rate' => 13,
                'default_duration_minutes' => 45,
                'reminder_days_before' => 5,
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $this->assertSame('Clínica Veterinaria San Roque', Setting::get('clinic_name'));
        $this->assertSame('33445566', Setting::get('clinic_phone'));
        $this->assertSame('contacto@sanroque.local', Setting::get('clinic_email'));
        $this->assertSame(13.0, Setting::get('tax_rate', null, 'billing'));
        $this->assertSame(45, Setting::get('default_duration_minutes', null, 'appointments'));
        $this->assertSame(5, Setting::get('reminder_days_before', null, 'appointments'));
    }

    public function test_users_without_the_settings_permission_cannot_open_the_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(ManageClinicSettings::getUrl())
            ->assertForbidden();
    }
}
