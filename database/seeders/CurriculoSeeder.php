<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Curriculo;
use App\Models\PlanEstudio;
use App\Models\PlanEstudio_Curriculo;

class CurriculoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $curr1=Curriculo::create([
            'nombre'=>'Curriculo Avanzado'
        ]);
        $curr2=Curriculo::create([
            'nombre'=>'Curriculo Base'
        ]);
        $curr3=Curriculo::create([
            'nombre'=>'Curriculo Experimental'
        ]);

        PlanEstudio_Curriculo::create([
            'id_curriculo'=>$curr1->id,
            'id_plan_estudio'=>1
        ]);
        PlanEstudio_Curriculo::create([
            'id_curriculo'=>$curr2->id,
            'id_plan_estudio'=>2
        ]);
        PlanEstudio_Curriculo::create([
            'id_curriculo'=>$curr3->id,
            'id_plan_estudio'=>1
        ]);
    }
}
