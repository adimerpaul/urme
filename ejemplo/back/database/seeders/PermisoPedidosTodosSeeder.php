<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermisoPedidosTodosSeeder extends Seeder
{
    public function run(): void
    {
        Permission::firstOrCreate(['name' => 'Ver todos los pedidos', 'guard_name' => 'web']);
    }
}
