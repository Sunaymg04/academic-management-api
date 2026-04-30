<?php

namespace Database\Seeders;

use App\Models\Curriculo_Disciplina;
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
        $dis1=Disciplina::create([
            'nombre'=>'Programacion',
            'fondo_tiempo'=>'50'
        ]);
        $dis2=Disciplina::create([
            'nombre'=>'Matematicas',
            'fondo_tiempo'=>'80'
        ]);
        $dis3=Disciplina::create([
            'nombre'=>'Ciencias Sociales',
            'fondo_tiempo'=>'40'
        ]);
        Curriculo_Disciplina::create([
            'id_disciplina'=>$dis1->id,
            'id_curriculo'=>1
        ]);
        Curriculo_Disciplina::create([
            'id_disciplina'=>$dis2->id,
            'id_curriculo'=>2
        ]);
        Curriculo_Disciplina::create([
            'id_disciplina'=>$dis3->id,
            'id_curriculo'=>3
        ]);
    }
}
