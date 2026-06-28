<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class EntregaResultadosPermisoSeeder extends Seeder
{
    public function run(): void
    {
        $permiso = Permission::firstOrCreate(['name' => 'Entrega de resultados', 'guard_name' => 'web']);

        // Dar el permiso a todos los usuarios que ya tienen 'Solicitudes'
        User::permission('Solicitudes')->each(fn($u) => $u->givePermissionTo($permiso));
    }
}
