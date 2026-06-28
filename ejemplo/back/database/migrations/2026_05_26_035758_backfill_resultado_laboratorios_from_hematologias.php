<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const COLUMNA_MAP = [
        'globulos rojos'                => 'globulos_rojos',
        'globulos blancos (leucocitos)' => 'globulos_blancos',
        'globulos blancos'              => 'globulos_blancos',
        'leucocitos totales'            => 'leucocitos_totales',
        'plaquetas'                     => 'plaquetas',
        'hemoglobina'                   => 'hemoglobina',
        'hematocrito'                   => 'hematocrito',
        'v.c.m.'                        => 'vcm',
        'vcm'                           => 'vcm',
        'hb.c.m.'                       => 'hbcm',
        'hbcm'                          => 'hbcm',
        'chcm'                          => 'chcm',
        'basofilos'                     => 'basofilos_porcentaje',
        'basilos (absoluto)'            => 'basofilos_absoluto',
        'basofilos (absoluto)'          => 'basofilos_absoluto',
        'eosinofilos'                   => 'eosinofilos_porcentaje',
        'eosinofilos (absoluto)'        => 'eosinofilos_absoluto',
        'cayados'                       => 'cayados_porcentaje',
        'cayados (absoluto)'            => 'cayados_absoluto',
        'segmentados'                   => 'segmentados_porcentaje',
        'segmentados (absoluto)'        => 'segmentados_absoluto',
        'linfocitos'                    => 'linfocitos_porcentaje',
        'linfocitos (absoluto)'         => 'linfocitos_absoluto',
        'monocitos'                     => 'monocitos_porcentaje',
        'monocitos (absoluto)'          => 'monocitos_absoluto',
        'blastos'                       => 'blastos_porcentaje',
        'metamielocito'                 => 'metamielocitos_porcentaje',
        'eritroblastos'                 => 'eritroblastos_porcentaje',
        'fibrinogeno'                   => 'fibrinogeno',
        'dimeros d'                     => 'dimeros_d',
        'reticulocitos'                 => 'ipr2',
        'ipr'                           => 'ipr',
        'rc'                            => 'rc',
        'tiempo protrombina'            => 'tiempo_protrombina',
        'actividad protrombina'         => 'actividad_protrombina',
        'actividad de protrombina'      => 'actividad_protrombina',
        'inr'                           => 'inr',
        'aptt'                          => 'aptt',
        'ves'                           => 'ves',
    ];

    private function norm(string $s): string
    {
        $map = [
            'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n',
            'Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ü'=>'U','Ñ'=>'N',
        ];
        return trim(mb_strtolower(strtr($s, $map)));
    }

    public function up(): void
    {
        $area = DB::table('areas')
            ->where(function ($q) {
                $q->where('title', 'HEMATOLOGÍA')
                  ->orWhere('title', 'Hematología')
                  ->orWhere('name', 'HEMATOLOGIA')
                  ->orWhere('name', 'Hematología');
            })
            ->first();

        if (!$area) {
            return;
        }

        $areaId = $area->id;
        $now    = now();

        $rangos = DB::table('area_rangos')
            ->where('area_id', $areaId)
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->whereNotNull('rango_minimo')->orWhereNotNull('rango_maximo');
            })
            ->get();

        $rangoColumna = [];
        foreach ($rangos as $rango) {
            $key = $this->norm($rango->rango_nombre);
            $col = self::COLUMNA_MAP[$key] ?? null;
            if ($col) {
                $rangoColumna[$rango->id] = ['col' => $col, 'unidad' => $rango->unidad];
            }
        }

        if (empty($rangoColumna)) {
            return;
        }

        DB::table('hematologias')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->chunk(200, function ($rows) use ($areaId, $rangoColumna, $now) {
                foreach ($rows as $h) {
                    foreach ($rangoColumna as $rangoId => $info) {
                        $valor = $h->{$info['col']} ?? null;
                        if ($valor === null || $valor === '') {
                            continue;
                        }

                        $existing = DB::table('resultado_laboratorios')
                            ->where('solicitude_id', $h->solicitude_id)
                            ->where('area_rango_id', $rangoId)
                            ->first();

                        if ($existing) {
                            DB::table('resultado_laboratorios')
                                ->where('id', $existing->id)
                                ->update([
                                    'valor_final' => (float) $valor,
                                    'area_id'     => $areaId,
                                    'unidad'      => $info['unidad'],
                                    'deleted_at'  => null,
                                    'updated_at'  => $now,
                                ]);
                        } else {
                            DB::table('resultado_laboratorios')->insert([
                                'solicitude_id' => $h->solicitude_id,
                                'area_rango_id' => $rangoId,
                                'area_id'       => $areaId,
                                'valor_final'   => (float) $valor,
                                'unidad'        => $info['unidad'],
                                'created_at'    => $now,
                                'updated_at'    => $now,
                            ]);
                        }
                    }
                }
            });
    }

    public function down(): void
    {
        $area = DB::table('areas')
            ->where(function ($q) {
                $q->where('title', 'HEMATOLOGÍA')
                  ->orWhere('title', 'Hematología')
                  ->orWhere('name', 'HEMATOLOGIA')
                  ->orWhere('name', 'Hematología');
            })
            ->first();

        if (!$area) {
            return;
        }

        DB::table('resultado_laboratorios')
            ->where('area_id', $area->id)
            ->delete();
    }
};
