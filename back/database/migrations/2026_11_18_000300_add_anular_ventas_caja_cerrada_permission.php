<?php

use App\Models\User;
use App\Support\Permisos;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permiso para anular una venta o un gasto de una caja ya cerrada (nunca de una
 * validada). Agrega el permiso y se lo da a admin; no modifica filas existentes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Permission::updateOrCreate(
            ['name' => 'Anular Ventas Caja Cerrada', 'guard_name' => 'web'],
            ['modulo' => 'Caja', 'descripcion' => Permisos::descripcionDe('Anular Ventas Caja Cerrada')],
        );
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        User::where('username', 'admin')->first()?->givePermissionTo('Anular Ventas Caja Cerrada');
    }

    public function down(): void
    {
        Permission::where('name', 'Anular Ventas Caja Cerrada')->where('guard_name', 'web')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
