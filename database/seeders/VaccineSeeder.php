<?php

namespace Database\Seeders;

use App\Models\Species;
use App\Models\Vaccine;
use Illuminate\Database\Seeder;

class VaccineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $vaccinesBySpecies = [
            'Canino' => [
                'Rabia' => 365,
                'Polivalente canina' => 365,
                'Bordetella' => 365,
                'Leptospirosis' => 365,
            ],
            'Felino' => [
                'Rabia' => 365,
                'Triple felina' => 365,
                'Leucemia felina' => 365,
            ],
            'Conejo' => [
                'Mixomatosis' => 365,
            ],
        ];

        $speciesByName = Species::query()->pluck('id', 'name');

        foreach ($vaccinesBySpecies as $speciesName => $vaccines) {
            $speciesId = $speciesByName->get($speciesName);

            if ($speciesId === null) {
                continue;
            }

            foreach ($vaccines as $vaccineName => $revaccinationDays) {
                Vaccine::query()->updateOrCreate(
                    ['species_id' => $speciesId, 'name' => $vaccineName],
                    [
                        'default_revaccination_days' => $revaccinationDays,
                        'active' => true,
                    ],
                );
            }
        }
    }
}
