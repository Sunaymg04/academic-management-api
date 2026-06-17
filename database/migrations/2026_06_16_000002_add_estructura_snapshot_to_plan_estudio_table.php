<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plan-estudio', function (Blueprint $table) {
            if (!Schema::hasColumn('plan-estudio', 'estructura_snapshot')) {
                $table->json('estructura_snapshot')->nullable()->after('plan_origen_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('plan-estudio', function (Blueprint $table) {
            if (Schema::hasColumn('plan-estudio', 'estructura_snapshot')) {
                $table->dropColumn('estructura_snapshot');
            }
        });
    }
};
