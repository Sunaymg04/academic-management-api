<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programa_de_formacion', function (Blueprint $table) {
            if (!Schema::hasColumn('programa_de_formacion', 'id_calificacion')) {
                $table->unsignedBigInteger('id_calificacion')->nullable()->unique()->after('abreviatura');
                $table->foreign('id_calificacion')->references('id')->on('calificacion')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('programa_de_formacion', function (Blueprint $table) {
            if (Schema::hasColumn('programa_de_formacion', 'id_calificacion')) {
                $table->dropForeign(['id_calificacion']);
                $table->dropColumn('id_calificacion');
            }
        });
    }
};
