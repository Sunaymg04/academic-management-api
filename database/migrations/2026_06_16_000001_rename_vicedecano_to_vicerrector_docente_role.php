<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('user_app_access') || !Schema::hasColumn('user_app_access', 'role')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE user_app_access MODIFY role ENUM('admin', 'vicedecano_docente', 'vicerrector_docente', 'decano', 'jefe_departamento', 'rector') NOT NULL");
        }

        DB::table('user_app_access')
            ->where('role', 'vicedecano_docente')
            ->update([
                'role' => 'vicerrector_docente',
                'facultad_id' => null,
                'departamento_id' => null,
            ]);

        if (Schema::hasTable('plan-estudio') && Schema::hasColumn('plan-estudio', 'estado')) {
            DB::table('plan-estudio')
                ->where('estado', 'enviado_vicedecano')
                ->update(['estado' => 'enviado_vicerrector']);
        }

        if (Schema::hasTable('modificacion') && Schema::hasColumn('modificacion', 'estado')) {
            DB::table('modificacion')
                ->where('estado', 'enviada_vicedecano')
                ->update(['estado' => 'enviada_vicerrector']);
        }

        if (Schema::hasTable('plan_notifications') && Schema::hasColumn('plan_notifications', 'type')) {
            $notificationTypes = [
                'plan_nuevo_enviado_vicedecano' => 'plan_nuevo_enviado_vicerrector',
                'plan_modificacion_enviada_vicedecano' => 'plan_modificacion_enviada_vicerrector',
                'plan_nuevo_aprobado_vicedecano' => 'plan_nuevo_aprobado_vicerrector',
                'plan_modificacion_aprobada_vicedecano' => 'plan_modificacion_aprobada_vicerrector',
                'plan_nuevo_rechazado_vicedecano' => 'plan_nuevo_rechazado_vicerrector',
                'plan_modificacion_cancelada_vicedecano' => 'plan_modificacion_cancelada_vicerrector',
            ];

            foreach ($notificationTypes as $oldType => $newType) {
                DB::table('plan_notifications')
                    ->where('type', $oldType)
                    ->update(['type' => $newType]);
            }
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE user_app_access MODIFY role ENUM('admin', 'vicerrector_docente', 'decano', 'jefe_departamento', 'rector') NOT NULL");
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('user_app_access') || !Schema::hasColumn('user_app_access', 'role')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE user_app_access MODIFY role ENUM('admin', 'vicedecano_docente', 'vicerrector_docente', 'decano', 'jefe_departamento', 'rector') NOT NULL");
        }

        DB::table('user_app_access')
            ->where('role', 'vicerrector_docente')
            ->update(['role' => 'vicedecano_docente']);

        if (Schema::hasTable('plan-estudio') && Schema::hasColumn('plan-estudio', 'estado')) {
            DB::table('plan-estudio')
                ->where('estado', 'enviado_vicerrector')
                ->update(['estado' => 'enviado_vicedecano']);
        }

        if (Schema::hasTable('modificacion') && Schema::hasColumn('modificacion', 'estado')) {
            DB::table('modificacion')
                ->where('estado', 'enviada_vicerrector')
                ->update(['estado' => 'enviada_vicedecano']);
        }

        if (Schema::hasTable('plan_notifications') && Schema::hasColumn('plan_notifications', 'type')) {
            $notificationTypes = [
                'plan_nuevo_enviado_vicerrector' => 'plan_nuevo_enviado_vicedecano',
                'plan_modificacion_enviada_vicerrector' => 'plan_modificacion_enviada_vicedecano',
                'plan_nuevo_aprobado_vicerrector' => 'plan_nuevo_aprobado_vicedecano',
                'plan_modificacion_aprobada_vicerrector' => 'plan_modificacion_aprobada_vicedecano',
                'plan_nuevo_rechazado_vicerrector' => 'plan_nuevo_rechazado_vicedecano',
                'plan_modificacion_cancelada_vicerrector' => 'plan_modificacion_cancelada_vicedecano',
            ];

            foreach ($notificationTypes as $oldType => $newType) {
                DB::table('plan_notifications')
                    ->where('type', $oldType)
                    ->update(['type' => $newType]);
            }
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE user_app_access MODIFY role ENUM('admin', 'vicedecano_docente', 'decano', 'jefe_departamento', 'rector') NOT NULL");
        }
    }
};
