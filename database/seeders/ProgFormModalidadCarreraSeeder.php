<?php

namespace Database\Seeders;

use App\Models\ModalidadCarrera;
use App\Models\ProgFormacion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProgFormModalidadCarreraSeeder extends Seeder
{
    public function run(): void
    {
        $programas = ProgFormacion::whereIn('abreviatura', ['II', 'CC', 'CI'])->get();

        if ($programas->isEmpty()) {
            return;
        }

        $modalidades = ModalidadCarrera::whereIn('nombre', [
            'Curso Diurno',
            'Curso por Encuentros',
            'Educación a Distancia',
        ])->get();

        foreach ($programas as $programa) {
            foreach ($modalidades as $modalidad) {
                DB::table('prog_form_modalidad_carrera')->updateOrInsert(
                    [
                        'id_modalidad' => $modalidad->id,
                        'id_prog_form' => $programa->id,
                    ],
                    [
                        'uuid' => Str::uuid(),
                        'id_modalidad' => $modalidad->id,
                        'id_prog_form' => $programa->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }
}
