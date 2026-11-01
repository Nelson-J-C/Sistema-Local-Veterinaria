<?php

namespace Database\Seeders;

use App\Models\Breed;
use App\Models\Species;
use Illuminate\Database\Seeder;

class BreedSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $breedsBySpecies = [
            'Canino' => [
                'Mestizo',
                'Labrador Retriever',
                'Golden Retriever',
                'Pastor Alemán',
                'Bulldog Francés',
                'Poodle',
                'Chihuahua',
                'Pitbull',
                'Rottweiler',
                'Schnauzer',
                'Cocker Spaniel',
                'Yorkshire Terrier',
                'Husky Siberiano',
                'Doberman',
                'Boxer',
                'Otro',
            ],
            'Felino' => [
                'Mestizo',
                'Persa',
                'Siamés',
                'Maine Coon',
                'Bengalí',
                'Angora',
                'Ragdoll',
                'Scottish Fold',
                'Otro',
            ],
            'Ave' => [
                'Periquito',
                'Canario',
                'Cotorra',
                'Ninfa',
                'Otro',
            ],
            'Conejo' => [
                'Enano',
                'Belier',
                'Angora',
                'Otro',
            ],
            'Roedor' => [
                'Hámster',
                'Cobaya',
                'Chinchilla',
                'Otro',
            ],
            'Reptil' => [
                'Tortuga',
                'Iguana',
                'Serpiente',
                'Otro',
            ],
            'Equino' => [
                'Criollo',
                'Cuarto de Milla',
                'Árabe',
                'Otro',
            ],
            'Otro' => [
                'Otro',
            ],
        ];

        $speciesByName = Species::query()->pluck('id', 'name');

        foreach ($breedsBySpecies as $speciesName => $breeds) {
            $speciesId = $speciesByName->get($speciesName);

            if ($speciesId === null) {
                continue;
            }

            foreach ($breeds as $breedName) {
                Breed::query()->updateOrCreate(
                    ['species_id' => $speciesId, 'name' => $breedName],
                    ['active' => true],
                );
            }
        }
    }
}
