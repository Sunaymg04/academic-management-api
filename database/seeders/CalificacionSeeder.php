<?php

namespace Database\Seeders;

use App\Models\Calificacion;
use App\Models\ProgFormacion;
use Illuminate\Database\Seeder;

class CalificacionSeeder extends Seeder
{
    public function run(): void
    {
        $calificacion = Calificacion::updateOrCreate(
            ['nombre' => 'Licenciado en Matemática'],
            ['nombre' => 'Licenciado en Matemática']
        );

        ProgFormacion::where('abreviatura', 'M')
            ->update(['id_calificacion' => $calificacion->id]);
    }
}
