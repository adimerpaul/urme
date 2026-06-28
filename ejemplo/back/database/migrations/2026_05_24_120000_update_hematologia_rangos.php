<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $area = DB::table('areas')
            ->where('title', 'HEMATOLOGÍA')
            ->orWhere('title', 'Hematología')
            ->orWhere('name', 'HEMATOLOGIA')
            ->orWhere('name', 'Hematología')
            ->first();

        if (! $area) {
            return;
        }

        $areaId = $area->id;
        $now = now();

        // Renombrar para que coincida con el lookup del front: 'Globulos Blancos (Leucocitos)'
        DB::table('area_rangos')
            ->where('area_id', $areaId)
            ->where('rango_nombre', 'Glóbulos Blancos')
            ->update(['rango_nombre' => 'Glóbulos Blancos (Leucocitos)']);

        $updates = [
            'Glóbulos Rojos' => [
                'rango_minimo'   => 4.2,
                'rango_maximo'   => 5.4,
                'unidad'         => 'X10⁶/µL',
                'interpretacion' => '4.2 - 5.4 X10⁶/µL',
            ],
            'Glóbulos Blancos (Leucocitos)' => [
                'rango_minimo'   => 4.0,
                'rango_maximo'   => 10.0,
                'unidad'         => 'X10³/µL',
                'interpretacion' => '4.0 - 10.0 X10³/µL',
            ],
            'Hematocrito' => [
                'rango_minimo'   => 0.44,
                'rango_maximo'   => 0.57,
                'unidad'         => 'L/L',
                'interpretacion' => '0.44 - 0.57 L/L',
            ],
            'FIBRINOGENO' => [
                'rango_minimo'   => 200,
                'rango_maximo'   => 400,
                'unidad'         => 'mg/dl',
                'interpretacion' => '200 - 400 mg/dl',
            ],
        ];

        foreach ($updates as $nombre => $data) {
            DB::table('area_rangos')
                ->where('area_id', $areaId)
                ->where('rango_nombre', $nombre)
                ->update(array_merge($data, ['updated_at' => $now]));
        }

        // Nuevos rangos (idempotente)
        $inserts = [
            [
                'rango_nombre'   => 'Dimeros D',
                'rango_minimo'   => 0,
                'rango_maximo'   => 0.40,
                'unidad'         => 'ug/ml',
                'interpretacion' => '0 - 0.40 ug/ml',
            ],
            [
                'rango_nombre'   => 'Reticulocitos',
                'rango_minimo'   => 0.5,
                'rango_maximo'   => 2.5,
                'unidad'         => '%',
                'interpretacion' => '0.5 - 2.5 %',
            ],
            [
                'rango_nombre'   => 'RC',
                'rango_minimo'   => null,
                'rango_maximo'   => null,
                'unidad'         => '%',
                'interpretacion' => null,
            ],
        ];

        foreach ($inserts as $row) {
            $exists = DB::table('area_rangos')
                ->where('area_id', $areaId)
                ->where('rango_nombre', $row['rango_nombre'])
                ->exists();

            if (! $exists) {
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
            ->where('title', 'HEMATOLOGÍA')
            ->orWhere('title', 'Hematología')
            ->orWhere('name', 'HEMATOLOGIA')
            ->orWhere('name', 'Hematología')
            ->first();

        if (! $area) {
            return;
        }

        $areaId = $area->id;

        DB::table('area_rangos')
            ->where('area_id', $areaId)
            ->whereIn('rango_nombre', ['Dimeros D', 'Reticulocitos', 'RC'])
            ->delete();

        DB::table('area_rangos')
            ->where('area_id', $areaId)
            ->where('rango_nombre', 'Glóbulos Blancos (Leucocitos)')
            ->update(['rango_nombre' => 'Glóbulos Blancos']);
    }
};
