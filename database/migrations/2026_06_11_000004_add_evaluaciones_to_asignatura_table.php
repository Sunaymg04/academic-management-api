<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asignatura', function (Blueprint $table) {
            $table->boolean('tiene_examen_final')->default(false)->after('horas_practica_laboral');
            $table->boolean('tiene_trabajo_curso')->default(false)->after('tiene_examen_final');
        });
    }

    public function down(): void
    {
        Schema::table('asignatura', function (Blueprint $table) {
            $table->dropColumn(['tiene_examen_final', 'tiene_trabajo_curso']);
        });
    }
};
