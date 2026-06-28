<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        Permission::firstOrCreate(['name' => 'Ver todos los pedidos', 'guard_name' => 'web']);
    }

    public function down(): void
    {
        Permission::where('name', 'Ver todos los pedidos')->delete();
    }
};
