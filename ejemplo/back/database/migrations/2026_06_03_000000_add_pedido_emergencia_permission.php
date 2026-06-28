<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        DB::table('permissions')->updateOrInsert(
            ['name' => 'Crear Pedidos de Emergencia', 'guard_name' => 'web'],
            ['created_at' => now(), 'updated_at' => now()]
        );
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        DB::table('permissions')
            ->where('name', 'Crear Pedidos de Emergencia')
            ->where('guard_name', 'web')
            ->delete();
    }
};
