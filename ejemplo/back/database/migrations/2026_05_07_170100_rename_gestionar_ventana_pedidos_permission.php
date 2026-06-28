<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('permissions')->insertOrIgnore([
            'name' => 'Herramientas de Almacén',
            'guard_name' => 'web',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $newPermissionId = DB::table('permissions')
            ->where('name', 'Herramientas de Almacén')
            ->where('guard_name', 'web')
            ->value('id');

        $oldPermissionId = DB::table('permissions')
            ->where('name', 'Gestionar Ventana Pedidos')
            ->where('guard_name', 'web')
            ->value('id');

        if ($newPermissionId && $oldPermissionId) {
            $roleAssignments = DB::table('role_has_permissions')
                ->where('permission_id', $oldPermissionId)
                ->get();

            foreach ($roleAssignments as $assignment) {
                DB::table('role_has_permissions')->insertOrIgnore([
                    'permission_id' => $newPermissionId,
                    'role_id' => $assignment->role_id,
                ]);
            }

            $assignments = DB::table('model_has_permissions')
                ->where('permission_id', $oldPermissionId)
                ->get();

            foreach ($assignments as $assignment) {
                DB::table('model_has_permissions')->insertOrIgnore([
                    'permission_id' => $newPermissionId,
                    'model_type' => $assignment->model_type,
                    'model_id' => $assignment->model_id,
                ]);
            }

            DB::table('role_has_permissions')->where('permission_id', $oldPermissionId)->delete();
            DB::table('model_has_permissions')->where('permission_id', $oldPermissionId)->delete();
            DB::table('permissions')->where('id', $oldPermissionId)->delete();
        }
    }

    public function down(): void
    {
        $now = now();

        DB::table('permissions')->insertOrIgnore([
            'name' => 'Gestionar Ventana Pedidos',
            'guard_name' => 'web',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
};
