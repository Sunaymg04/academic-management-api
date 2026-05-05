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
        DB::table('disciplina_asignatura')->insert([

            // 🔹 DISCIPLINA 1 (Programacion)
            [
                'id' => Str::uuid(),
                'id_disciplina' => 1,
                'id_asignatura' => 2,
                'created_at'=>now(),
                'updated_at'=>now(),
            ],
            [
                'id' => Str::uuid(),
                'id_disciplina' => 1,
                'id_asignatura' => 4,
                'created_at'=>now(),
                'updated_at'=>now(),
            ],
            [
                'id' => Str::uuid(),
                'id_disciplina' => 1,
                'id_asignatura' => 3,
                'created_at'=>now(),
                'updated_at'=>now(),
            ],
            [
                'id' => Str::uuid(),
                'id_disciplina' => 1,
                'id_asignatura' => 9,
                'created_at'=>now(),
                'updated_at'=>now(),
            ],
            [
                'id' => Str::uuid(),
                'id_disciplina' => 1,
                'id_asignatura' => 10,
                'created_at'=>now(),
                'updated_at'=>now(),
            ],
            [
                'id' => Str::uuid(),
                'id_disciplina' => 1,
                'id_asignatura' => 11,
                'created_at'=>now(),
                'updated_at'=>now(),
            ],
            [
                'id' => Str::uuid(),
                'id_disciplina' => 1,
                'id_asignatura' => 12,
                'created_at'=>now(),
                'updated_at'=>now(),
            ],

            // 🔹 DISCIPLINA 2 (Matematicas)
            [
                'id' => Str::uuid(),
                'id_disciplina' => 2,
                'id_asignatura' => 1,
                'created_at'=>now(),
                'updated_at'=>now(),
            ],
            [
                'id' => Str::uuid(),
                'id_disciplina' => 2,
                'id_asignatura' => 13,
                'created_at'=>now(),
                'updated_at'=>now(),
            ],
            [
                'id' => Str::uuid(),
                'id_disciplina' => 2,
                'id_asignatura' => 14,
                'created_at'=>now(),
                'updated_at'=>now(),
            ],

            // 🔹 DISCIPLINA 3 (Ciencias Sociales)
            [
                'id' => Str::uuid(),
                'id_disciplina' => 3,
                'id_asignatura' => 5,
                'created_at'=>now(),
                'updated_at'=>now(),
            ],
            [
                'id' => Str::uuid(),
                'id_disciplina' => 3,
                'id_asignatura' => 6,
                'created_at'=>now(),
                'updated_at'=>now(),
            ],
            [
                'id' => Str::uuid(),
                'id_disciplina' => 3,
                'id_asignatura' => 7,
                'created_at'=>now(),
                'updated_at'=>now(),
            ],
            [
                'id' => Str::uuid(),
                'id_disciplina' => 3,
                'id_asignatura' => 8,
                'created_at'=>now(),
                'updated_at'=>now(),
            ],
        ]);
    }
}
