<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alumno_ayudante', function (Blueprint $table) {
            if (!Schema::hasColumn('alumno_ayudante', 'id_curso')) {
                $table->unsignedBigInteger('id_curso')->nullable()->after('id_estudiante');
                $table->foreign('id_curso')->references('id')->on('curso')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('alumno_ayudante', function (Blueprint $table) {
            if (Schema::hasColumn('alumno_ayudante', 'id_curso')) {
                $table->dropForeign(['id_curso']);
                $table->dropColumn('id_curso');
            }
        });
    }
};
