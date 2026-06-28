<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class ReportesPermisoSeeder extends Seeder
{
    public function run(): void
    {
        Permission::firstOrCreate(['name' => 'Reportes', 'guard_name' => 'web']);
    }
}
