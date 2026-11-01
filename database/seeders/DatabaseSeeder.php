<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            SettingsSeeder::class,
            SpeciesSeeder::class,
            BreedSeeder::class,
            VaccineSeeder::class,
            ServiceSeeder::class,
        ]);

        $this->createAdministrator();
    }

    /**
     * Create the initial administrator account and assign the admin role.
     */
    protected function createAdministrator(): void
    {
        $admin = User::query()->firstOrNew(['email' => config('clinic.admin_email')]);

        if (! $admin->exists && blank(config('clinic.admin_password'))) {
            $this->command?->warn(
                'No se creó el usuario administrador: defina CLINIC_ADMIN_PASSWORD en el archivo .env.',
            );

            return;
        }

        $admin->name = 'Administrador';
        $admin->active = true;

        if (! $admin->exists) {
            $admin->password = config('clinic.admin_password');
        }

        $admin->save();
        $admin->assignRole('admin');
    }
}
