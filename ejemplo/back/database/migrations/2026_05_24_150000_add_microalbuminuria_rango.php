<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Área 2 = QUIMICA SANGUINEA
        $exists = DB::table('area_rangos')
            ->where('area_id', 2)
            ->whereRaw("LOWER(rango_nombre) LIKE '%microalbuminuria%'")
            ->exists();

        if (! $exists) {
            DB::table('area_rangos')->insert([
                'area_id'        => 2,
                'rango_nombre'   => 'Microalbuminuria',
                'rango_minimo'   => null,
                'rango_maximo'   => 30.0,
                'unidad'         => 'mg/L',
                'interpretacion' => 'Normal: < 30 mg/L',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('area_rangos')
            ->where('area_id', 2)
            ->whereRaw("LOWER(rango_nombre) LIKE '%microalbuminuria%'")
            ->delete();
    }
};
