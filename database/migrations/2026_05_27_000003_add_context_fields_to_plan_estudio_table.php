<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plan-estudio', function (Blueprint $table) {
            if (!Schema::hasColumn('plan-estudio', 'id_prog_form')) {
                $table->unsignedBigInteger('id_prog_form')->nullable()->after('nombre');
                $table->foreign('id_prog_form')->references('id')->on('programa_de_formacion')->nullOnDelete();
            }

            if (!Schema::hasColumn('plan-estudio', 'id_curso')) {
                $table->unsignedBigInteger('id_curso')->nullable()->after('id_prog_form');
                $table->foreign('id_curso')->references('id')->on('curso')->nullOnDelete();
            }

            if (!Schema::hasColumn('plan-estudio', 'id_modalidad')) {
                $table->unsignedBigInteger('id_modalidad')->nullable()->after('id_curso');
                $table->foreign('id_modalidad')->references('id')->on('modalidad_carrera')->nullOnDelete();
            }

            if (!Schema::hasColumn('plan-estudio', 'id_calificacion')) {
                $table->unsignedBigInteger('id_calificacion')->nullable()->after('id_modalidad');
                $table->foreign('id_calificacion')->references('id')->on('calificacion')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('plan-estudio', function (Blueprint $table) {
            foreach (['id_calificacion', 'id_modalidad', 'id_curso', 'id_prog_form'] as $column) {
                if (Schema::hasColumn('plan-estudio', $column)) {
                    $table->dropForeign([$column]);
                    $table->dropColumn($column);
                }
            }
        });
    }
};
