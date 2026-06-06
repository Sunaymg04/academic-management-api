<?php

namespace Database\Seeders;

use App\Models\Curriculo_Disciplina;
use App\Models\Curriculo;
use App\Models\Disciplina;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DisciplinaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Disciplina::where('nombre', 'Matematicas')->update([
            'nombre' => 'Matematica Superior',
        ]);

        $disciplinas = [
            
            ['nombre' => 'Matematica Superior', 'fondo_tiempo' => 320],
            
            ['nombre' => 'Marxismo Leninismo', 'fondo_tiempo' => 184],
            ['nombre' => 'Historia de Cuba', 'fondo_tiempo' => 56],
            ['nombre' => 'Preparacion para la Defensa', 'fondo_tiempo' => 68],
            ['nombre' => 'Economia Empresarial', 'fondo_tiempo' => 48],
            ['nombre' => 'Infraestructuras de Sistemas Informaticos', 'fondo_tiempo' => 240],
            ['nombre' => 'Inteligencia Computacional', 'fondo_tiempo' => 320],
            ['nombre' => 'Ingenieria y Gestion de Software', 'fondo_tiempo' => 562],
            ['nombre' => 'Practica Profesional', 'fondo_tiempo' => 1042],
            ['nombre' => 'Educacion Fisica', 'fondo_tiempo' => 112],
            ['nombre' => 'Asignaturas Curriculo Propio', 'fondo_tiempo' => 498],
            ['nombre' => 'Asignaturas Optativas y Electivas', 'fondo_tiempo' => 314],
        ];

        foreach ($disciplinas as $disciplinaData) {
            Disciplina::updateOrCreate([
                'nombre' => $disciplinaData['nombre']
            ], [
                'fondo_tiempo' => $disciplinaData['fondo_tiempo'],
            ]);
        }

        $curriculoBase = Curriculo::where('nombre', 'Curriculo Base')->first();
        $curriculoPropio = Curriculo::where('nombre', 'Curriculo Propio')->first();
        $curriculoOptativo = Curriculo::where('nombre', 'Curriculo Optativo Electivo')->first();

        $disciplinasBase = [
            'Matematica Superior',
            'Marxismo Leninismo',
            'Historia de Cuba',
            'Preparacion para la Defensa',
            'Economia Empresarial',
            'Infraestructuras de Sistemas Informaticos',
            'Inteligencia Computacional',
            'Ingenieria y Gestion de Software',
            'Practica Profesional',
            'Educacion Fisica',
        ];

        foreach ($disciplinasBase as $nombreDisciplina) {
            $disciplina = Disciplina::where('nombre', $nombreDisciplina)->first();

            if ($curriculoBase && $disciplina) {
                Curriculo_Disciplina::firstOrCreate([
                    'id_curriculo' => $curriculoBase->id,
                    'id_disciplina' => $disciplina->id,
                ]);
            }
        }

        $disciplinaPropio = Disciplina::where('nombre', 'Asignaturas Curriculo Propio')->first();
        if ($curriculoPropio && $disciplinaPropio) {
            Curriculo_Disciplina::firstOrCreate([
                'id_curriculo' => $curriculoPropio->id,
                'id_disciplina' => $disciplinaPropio->id,
            ]);
        }

        $disciplinaOptativo = Disciplina::where('nombre', 'Asignaturas Optativas y Electivas')->first();
        if ($curriculoOptativo && $disciplinaOptativo) {
            Curriculo_Disciplina::firstOrCreate([
                'id_curriculo' => $curriculoOptativo->id,
                'id_disciplina' => $disciplinaOptativo->id,
            ]);
        }

        // If the optativo curriculum used a slightly different name or the relation
        // was not created, attempt robust linking: try name variants and fallback
        // to curriculum id 3 which is the expected optativo/electivo curriculum.
        $optativoNames = ['Curriculo Optativo Electivo', 'Curriculo Optativo/Electivo', 'Curriculo Optativo Electivo'];
        $curriculoOptativoVariant = Curriculo::whereIn('nombre', $optativoNames)->first();
        if (! $curriculoOptativoVariant) {
            $curriculoOptativoVariant = Curriculo::find(3);
        }

        $disciplinaOptativoVariant = Disciplina::where('nombre', 'Asignaturas Optativas y Electivas')
            ->orWhere('nombre', 'Asignaturas Optativas  y Electivas')
            ->orWhere('nombre', 'Asignaturas Optativas y Electivas')
            ->first();

        if ($curriculoOptativoVariant && $disciplinaOptativoVariant) {
            Curriculo_Disciplina::firstOrCreate([
                'id_curriculo' => $curriculoOptativoVariant->id,
                'id_disciplina' => $disciplinaOptativoVariant->id,
            ]);
        }
    }
}
