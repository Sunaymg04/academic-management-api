<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resolucion_configuraciones', function (Blueprint $table) {
            if (!Schema::hasColumn('resolucion_configuraciones', 'updated_by')) {
                $table->string('updated_by')->nullable()->after('fields');
            }
        });
    }

    public function down(): void
    {
        Schema::table('resolucion_configuraciones', function (Blueprint $table) {
            if (Schema::hasColumn('resolucion_configuraciones', 'updated_by')) {
                $table->dropColumn('updated_by');
            }
        });
    }
};
