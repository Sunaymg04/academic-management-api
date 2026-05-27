<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resolucion_configuraciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('facultad_id');
            $table->string('tipo', 30);
            $table->json('fields')->nullable();
            $table->timestamps();

            $table->foreign('facultad_id')->references('id')->on('facultad')->cascadeOnDelete();
            $table->unique(['facultad_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resolucion_configuraciones');
    }
};
