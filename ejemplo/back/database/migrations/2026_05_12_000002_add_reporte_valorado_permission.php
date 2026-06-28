<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $guard = 'web';
        $now = now();

        DB::table('permissions')->insertOrIgnore([
            'name' => 'Reporte Valorado',
            'guard_name' => $guard,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        DB::table('permissions')->where('name', 'Reporte Valorado')->delete();
    }
};
