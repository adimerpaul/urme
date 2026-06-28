<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permiso = Permission::firstOrCreate(['name' => 'Entrega de resultados', 'guard_name' => 'web']);

        // Asignar a todos los usuarios que ya tienen el permiso 'Solicitudes'
        $solicitudesId = DB::table('permissions')->where('name', 'Solicitudes')->value('id');
        if ($solicitudesId) {
            $userIds = DB::table('model_has_permissions')
                ->where('permission_id', $solicitudesId)
                ->where('model_type', 'App\\Models\\User')
                ->pluck('model_id');

            foreach ($userIds as $userId) {
                DB::table('model_has_permissions')->insertOrIgnore([
                    'permission_id' => $permiso->id,
                    'model_type'    => 'App\\Models\\User',
                    'model_id'      => $userId,
                ]);
            }
        }
    }

    public function down(): void
    {
        $permiso = DB::table('permissions')->where('name', 'Entrega de resultados')->first();
        if ($permiso) {
            DB::table('model_has_permissions')->where('permission_id', $permiso->id)->delete();
            DB::table('permissions')->where('id', $permiso->id)->delete();
        }
    }
};
