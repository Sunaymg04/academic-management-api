<?php

namespace Database\Seeders;

use App\Models\Calificacion;
use App\Models\ProgFormacion;
use Illuminate\Database\Seeder;

class CalificacionSeeder extends Seeder
{
    public function run(): void
    {
        $calificaciones = [
            [
                'nombre' => 'Ingeniero Informático',
                'programa' => 'II',
            ],
            [
                'nombre' => 'Licenciado en Ciencias de la Computación',
                'programa' => 'CC',
            ],
            [
                'nombre' => 'Licenciado en Ciencias de la Información',
                'programa' => 'CI',
            ],
        ];

        foreach ($calificaciones as $item) {
            $calificacion = Calificacion::updateOrCreate(
                ['nombre' => $item['nombre']],
                ['nombre' => $item['nombre']]
            );

            ProgFormacion::where('abreviatura', $item['programa'])
                ->update(['id_calificacion' => $calificacion->id]);
        }
    }
}
