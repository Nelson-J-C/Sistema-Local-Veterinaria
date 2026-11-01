<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $services = [
            ['CONS-GEN', 'Consulta general', 'consulta', 'Evaluación clínica general', 10000, 30],
            ['CONS-EME', 'Consulta de emergencia', 'consulta', 'Atención fuera de horario', 15000, 30],
            ['VACU', 'Vacunación', 'preventivo', 'Aplicación de vacuna', 5000, 15],
            ['DESP', 'Desparasitación', 'preventivo', 'Desparasitación interna o externa', 4000, 15],
            ['BANO', 'Baño y peluquería', 'estetica', 'Baño, corte y secado', 8000, 60],
            ['CIR-MEN', 'Cirugía menor', 'cirugia', 'Procedimiento quirúrgico ambulatorio', 50000, 60],
            ['CIR-MAY', 'Cirugía mayor', 'cirugia', 'Procedimiento quirúrgico complejo', 120000, 120],
            ['HOSP-DIA', 'Hospitalización diaria', 'hospitalizacion', 'Cuidado hospitalario por día', 15000, null],
            ['LAB-HEM', 'Laboratorio - hemograma', 'laboratorio', 'Hemograma completo', 12000, null],
            ['LAB-BIO', 'Laboratorio - bioquímica', 'laboratorio', 'Perfil bioquímico', 18000, null],
            ['IMG-RX', 'Radiografía', 'imagen', 'Estudio radiográfico', 20000, 30],
            ['IMG-ECO', 'Ecografía', 'imagen', 'Estudio ecográfico', 25000, 30],
            ['EUTA', 'Eutanasia', 'procedimiento', 'Procedimiento humanitario', 30000, 30],
        ];

        foreach ($services as [$code, $name, $category, $description, $priceCents, $durationMinutes]) {
            Service::query()->updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'category' => $category,
                    'description' => $description,
                    'price_cents' => $priceCents,
                    'tax_rate' => config('clinic.tax_rate'),
                    'duration_minutes' => $durationMinutes,
                    'active' => true,
                ],
            );
        }
    }
}
