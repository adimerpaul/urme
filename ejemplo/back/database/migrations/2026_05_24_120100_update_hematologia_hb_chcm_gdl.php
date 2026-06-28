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

        $updates = [
            'Hemoglobina' => [
                'rango_minimo'   => 15.7,
                'rango_maximo'   => 18.4,
                'unidad'         => 'g/dL',
                'interpretacion' => 'Varón 21-60: 16.9-18.4 g/dL; Mujer 21-60: 15.7-17.4 g/dL',
            ],
            'CHCM' => [
                'rango_minimo'   => 33,
                'rango_maximo'   => 36,
                'unidad'         => 'g/dL',
                'interpretacion' => '33 - 36 g/dL',
            ],
        ];

        foreach ($updates as $nombre => $data) {
            DB::table('area_rangos')
                ->where('area_id', $areaId)
                ->where('rango_nombre', $nombre)
                ->update(array_merge($data, ['updated_at' => $now]));
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
        $now = now();

        $rollback = [
            'Hemoglobina' => [
                'rango_minimo'   => 157,
                'rango_maximo'   => 184,
                'unidad'         => 'g/L',
                'interpretacion' => 'Varón 21-60 años: 169-184 g/L; Mujer 21-60 años: 157-174 g/L',
            ],
            'CHCM' => [
                'rango_minimo'   => 338,
                'rango_maximo'   => 342,
                'unidad'         => 'g/L',
                'interpretacion' => '340 +/- 2 g/L',
            ],
        ];

        foreach ($rollback as $nombre => $data) {
            DB::table('area_rangos')
                ->where('area_id', $areaId)
                ->where('rango_nombre', $nombre)
                ->update(array_merge($data, ['updated_at' => $now]));
        }
    }
};
