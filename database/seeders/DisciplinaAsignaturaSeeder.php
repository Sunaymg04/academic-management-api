<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DisciplinaAsignaturaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('disciplina_asignatura')->delete();

        $disciplinas = DB::table('disciplina')->pluck('id', 'nombre');
        $asignaturas = DB::table('asignatura')->pluck('id', 'nombre');

        $relaciones = [
            'Matematica Superior' => [
                'Matematica I',
                'Matematica II',
                'Matematica III',
                'Matematica Numerica',
            ],
            'Marxismo Leninismo' => [
                'Filosofia',
                'Economia Politica',
                'Teoria Politica',
                'Estudios de Ciencia, Tecnologia y Sociedad',
            ],
            'Historia de Cuba' => [
                'Historia de Cuba',
            ],
            'Preparacion para la Defensa' => [
                'Defensa y Seguridad Nacional',
            ],
            'Economia Empresarial' => [
                'Economia Empresarial',
            ],
            'Infraestructuras de Sistemas Informaticos' => [
                'Arquitectura de Computadoras',
                'Sistemas Operativos',
                'Redes de Computadoras',
                'Seguridad Informatica',
            ],
            'Inteligencia Computacional' => [
                'Logica Matematica',
                'Matematica Computacional',
                'Inteligencia Artificial I',
                'Inteligencia Artificial II',
                'Probabilidades y Estadistica',
                'Investigacion de Operaciones',
            ],
            'Ingenieria y Gestion de Software' => [
                'Fundamentos de la Informatica',
                'Introduccion a la Programacion',
                'Diseno y Programacion Orientada a Objetos',
                'Estructuras de Datos',
                'Bases de Datos',
                'Ingenieria de Software I',
                'Ingenieria de Software II',
                'Programacion Web',
            ],
            'Practica Profesional' => [
                'Seminario Profesional',
                'Seminario Profesional 2do ano',
                'Seminario Profesional 3er ano',
                'Trabajo de Diploma',
            ],
            'Educacion Fisica' => [
                'Educacion Fisica I',
                'Educacion Fisica II',
                'Educacion Fisica III',
                'Educacion Fisica IV',
            ],
            'Asignaturas Curriculo Propio' => [
                'Modelado y Diseno de Interfaces',
                'Fisica',
                'Taller de Bases de Datos',
                'Desarrollo de Aplicaciones Moviles',
                'Bases de Datos para la Toma de Decisiones',
                'Gestion de Software',
                'Proyecto de Trabajo de Diploma',
            ],
            'Asignaturas Optativas y Electivas' => [
                'Electiva 1',
                'Optativa 1',
                'Optativa 2',
                'Optativa 3',
                'Optativa 4',
            ],
        ];

        $rows = [];

        foreach ($relaciones as $nombreDisciplina => $nombresAsignaturas) {
            $idDisciplina = $disciplinas[$nombreDisciplina] ?? null;

            if (! $idDisciplina) {
                continue;
            }

            foreach ($nombresAsignaturas as $nombreAsignatura) {
                $idAsignatura = $asignaturas[$nombreAsignatura] ?? null;

                if (! $idAsignatura) {
                    continue;
                }

                $rows[] = [
                    'id' => Str::uuid(),
                    'id_disciplina' => $idDisciplina,
                    'id_asignatura' => $idAsignatura,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if (! empty($rows)) {
            DB::table('disciplina_asignatura')->insert($rows);
        }
    }
}
