<?php

namespace Database\Seeders;

use App\Models\Species;
use Illuminate\Database\Seeder;

class SpeciesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $species = [
            'Canino',
            'Felino',
            'Ave',
            'Conejo',
            'Roedor',
            'Reptil',
            'Equino',
            'Otro',
        ];

        foreach ($species as $name) {
            Species::query()->updateOrCreate(
                ['name' => $name],
                ['active' => true],
            );
        }
    }
}
