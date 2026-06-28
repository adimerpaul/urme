<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $area = DB::table('areas')
            ->where('id', 2)
            ->orWhere('title', 'QUIMICA SANGUINEA')
            ->orWhere('title', 'Química sanguínea')
            ->orWhere('name', 'QUIMICA SANGUINEA')
            ->orWhereRaw("LOWER(title) LIKE '%quimica%sanguinea%'")
            ->first();

        if (! $area) {
            return;
        }

        $areaId = $area->id;
        $now = now();

        // Actualizar Hb glicosilada (3.5 - 5.8 %)
        DB::table('area_rangos')
            ->where('area_id', $areaId)
            ->where('rango_nombre', 'Hb glicosilada')
            ->update([
                'rango_minimo'   => 3.5,
                'rango_maximo'   => 5.8,
                'unidad'         => '%',
                'interpretacion' => '3.5 - 5.8 %',
                'updated_at'     => $now,
            ]);

        // Insertar/actualizar rangos nuevos
        $inserts = [
            [
                'rango_nombre'   => 'Transferrina (TRF)',
                'rango_minimo'   => 2.00,
                'rango_maximo'   => 3.60,
                'unidad'         => 'g/L',
                'interpretacion' => '2.00 - 3.60 g/L',
            ],
            [
                'rango_nombre'   => 'Microalbuminuria',
                'rango_minimo'   => 0,
                'rango_maximo'   => 30,
                'unidad'         => 'mg/L',
                'interpretacion' => '< 30 mg/L',
            ],
            [
                'rango_nombre'   => 'Creatinina en orina (Creatinuria casual)',
                'rango_minimo'   => 28,
                'rango_maximo'   => 259,
                'unidad'         => 'mg/dl',
                'interpretacion' => 'H: 39-259 mg/dl; M: 28-217 mg/dl',
            ],
            [
                'rango_nombre'   => 'Depuración de Creatinina Endógena (DCE)',
                'rango_minimo'   => 88,
                'rango_maximo'   => 137,
                'unidad'         => 'mL/min',
                'interpretacion' => 'H: 97-137 mL/min; M: 88-128 mL/min',
            ],
            [
                'rango_nombre'   => 'NUS',
                'rango_minimo'   => 6,
                'rango_maximo'   => 20,
                'unidad'         => 'mg/dl',
                'interpretacion' => '6 - 20 mg/dl',
            ],
        ];

        foreach ($inserts as $row) {
            $existing = DB::table('area_rangos')
                ->where('area_id', $areaId)
                ->where('rango_nombre', $row['rango_nombre'])
                ->first();

            if ($existing) {
                DB::table('area_rangos')
                    ->where('id', $existing->id)
                    ->update(array_merge($row, ['updated_at' => $now]));
            } else {
                $row['area_id']    = $areaId;
                $row['created_at'] = $now;
                $row['updated_at'] = $now;
                DB::table('area_rangos')->insert($row);
            }
        }
    }

    public function down(): void
    {
        $area = DB::table('areas')
            ->where('title', 'QUIMICA SANGUINEA')
            ->orWhere('title', 'Química sanguínea')
            ->orWhere('name', 'QUIMICA SANGUINEA')
            ->first();

        if (! $area) {
            return;
        }

        $areaId = $area->id;

        DB::table('area_rangos')
            ->where('area_id', $areaId)
            ->whereIn('rango_nombre', [
                'Transferrina (TRF)',
                'Microalbuminuria',
                'Creatinina en orina (Creatinuria casual)',
                'Depuración de Creatinina Endógena (DCE)',
            ])
            ->delete();
    }
};
