<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

use function Symfony\Component\Clock\now;

class PlanEstudioProgFormSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('plan_de_estudio_programa_de_formacion')->insert([
            [
                'plan_estudio_id'=>1,
                'programa_de_formacion_id'=>1,
                'created_at'=>now(),
                'updated_at'=>now(),
            ],
            [
                'plan_estudio_id'=>2,
                'programa_de_formacion_id'=>2,
                'created_at'=>now(),
                'updated_at'=>now(),
            ]
        ]);
    }
}
