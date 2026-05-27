<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AnoAcademico;
use App\Models\Curso;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AgnoAcademicoCursoSeeder extends Seeder
{
    public function run(): void
    {
        $cohorteId = DB::table('cohorte')->value('id');

        if (!$cohorteId) {
            return;
        }

        DB::table('a_academico_curso')->delete();

        $now = now();
        $registros = [];

        foreach (AnoAcademico::query()->pluck('id') as $anoAcademicoId) {
            foreach (Curso::query()->pluck('id') as $cursoId) {
                $registros[] = [
                    'id' => (string) Str::uuid(),
                    'id_a_academico' => $anoAcademicoId,
                    'id_curso' => $cursoId,
                    'id_cohorte' => $cohorteId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($registros, 500) as $chunk) {
            DB::table('a_academico_curso')->insert($chunk);
        }
    }
}
