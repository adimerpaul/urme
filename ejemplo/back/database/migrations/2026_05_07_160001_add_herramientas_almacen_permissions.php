<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $guard = 'web';
        $now = now();
        $perms = [
            'Ver Herramientas Almacén',
            'Herramientas de Almacén',
        ];

        foreach ($perms as $name) {
            DB::table('permissions')->insertOrIgnore([
                'name' => $name,
                'guard_name' => $guard,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('permissions')->whereIn('name', [
            'Ver Herramientas Almacén',
            'Herramientas de Almacén',
        ])->delete();
    }
};
