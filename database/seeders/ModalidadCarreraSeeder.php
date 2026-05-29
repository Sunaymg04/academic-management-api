<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ModalidadCarreraSeeder extends Seeder
{
    public function run(): void
    {
        $modalidades = [
            'Curso Diurno',
            'Curso por Encuentros',
            'Educación a Distancia',
        ];

        foreach ($modalidades as $modalidad) {
            DB::table('modalidad_carrera')->updateOrInsert(
                ['nombre' => $modalidad],
                [
                    'nombre' => $modalidad,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
