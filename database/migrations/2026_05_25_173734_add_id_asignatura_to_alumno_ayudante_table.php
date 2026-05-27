<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alumno_ayudante', function (Blueprint $table) {
            if (!Schema::hasColumn('alumno_ayudante', 'id_asignatura')) {
                $table->unsignedBigInteger('id_asignatura')->nullable()->after('id_estudiante');
                $table->foreign('id_asignatura')->references('id')->on('asignatura')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('alumno_ayudante', function (Blueprint $table) {
            if (Schema::hasColumn('alumno_ayudante', 'id_asignatura')) {
                $table->dropForeign(['id_asignatura']);
                $table->dropColumn('id_asignatura');
            }
        });
    }
};
