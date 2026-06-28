<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AreaTipoMuestraSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $data = [
            // 1) HEMATOLOGÍA  (area_id = 1)
            [
                'area_id'      => 1,
                'tipo_muestra' => 'Hemograma (tapa lila)',
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'area_id'      => 1,
                'tipo_muestra' => 'TP plasma (tapa celeste 1:9)',
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'area_id'      => 1,
                'tipo_muestra' => 'VSG (tapa celeste 1:4)',
                'created_at'   => $now,
                'updated_at'   => $now,
            ],

            // 2) QUÍMICA SANGUÍNEA Y SEROLOGÍA  (area_id = 2)
            [
                'area_id'      => 2,
                'tipo_muestra' => 'Suero',
                'created_at'   => $now,
                'updated_at'   => $now,
            ],

            // 3) UROANÁLISIS (Uroanálisis y parasitología)  (area_id = 3)
            [
                'area_id'      => 3,
                'tipo_muestra' => 'Orinas',
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'area_id'      => 3,
                'tipo_muestra' => 'Heces',
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'area_id'      => 3,
                'tipo_muestra' => 'Sangre capilar',
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'area_id'      => 3,
                'tipo_muestra' => 'Cutánea',
                'created_at'   => $now,
                'updated_at'   => $now,
            ],

            // 4) MICROBIOLOGÍA  (area_id = 4)
            [
                'area_id'      => 4,
                'tipo_muestra' => 'Orina',
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'area_id'      => 4,
                'tipo_muestra' => 'Heces',
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'area_id'      => 4,
                'tipo_muestra' => 'Líquidos',
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'area_id'      => 4,
                'tipo_muestra' => 'Secreciones',
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'area_id'      => 4,
                'tipo_muestra' => 'Otros',
                'created_at'   => $now,
                'updated_at'   => $now,
            ],

            // 5) INMUNOLOGÍA / INFECCIOSOS  (area_id = 5)
            [
                'area_id'      => 5,
                'tipo_muestra' => 'Sueros',
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'area_id'      => 5,
                'tipo_muestra' => 'Heces',
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'area_id'      => 5,
                'tipo_muestra' => 'Plasma',
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
//            Orina
//Sangre
//Estupo
//LCR
//Secreción
//Secreción Vaginal
//Secreción Uretral
//Secreción Brazo Izquierdo
//Secreción Celulosa
//Cavidad Abdominal
//Hisopado Faringeo
//Espermacultivo
//Punta de Cateter
//Hueco Pupliteo Izquierdo
//Liquido Peritonal
//Apendice
//Otros
            [
                'area_id'      => 5,
                'tipo_muestra' => 'Orina',
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'area_id'      => 5,
                'tipo_muestra' => 'Sangre',
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'area_id'      => 5,
                'tipo_muestra' => 'LCR',
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'area_id'      => 5,
                'tipo_muestra' => 'Secreción Vaginal',
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'area_id'      => 5,
                'tipo_muestra' => 'Secreción Uretral',
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'area_id'      => 5,
                'tipo_muestra' => 'Hisopado Faríngeo',
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'area_id'      => 5,
                'tipo_muestra' => 'Liquido Peritoneal',
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'area_id'      => 5,
                'tipo_muestra' => 'Otros',
                'created_at'   => $now,
                'updated_at'   => $now,
            ],

            // 6) BIOLOGÍA MOLECULAR  (area_id = 6)
            [
                'area_id'      => 6,
                'tipo_muestra' => 'Hisopado',
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'area_id'      => 6,
                'tipo_muestra' => 'Secreciones',
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
//            area7
//            Hisopado Cervical
//Hisopado Ondetral
//Hisopado Bucal
//Hisopado NasoFaringeo
//Hisopado Faringeo
//Aspirado
//Hisopado Combinado
//Hisopado OroFaringeo
//Hisopado Uretral
//Hisopado Vaginal
//Otros
            [
                'area_id'      => 7,
                'tipo_muestra' => 'Hisopado Cervical',
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'area_id'      => 7,
                'tipo_muestra' => 'Hisopado Ondetral',
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'area_id'      => 7,
                'tipo_muestra' => 'Hisopado Bucal',
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'area_id'      => 7,
                'tipo_muestra' => 'Hisopado NasoFaringeo',
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'area_id'      => 7,
                'tipo_muestra' => 'Hisopado Faringeo',
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'area_id'      => 7,
                'tipo_muestra' => 'Aspirado',
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'area_id'      => 7,
                'tipo_muestra' => 'Hisopado Combinado',
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'area_id'      => 7,
                'tipo_muestra' => 'Hisopado OroFaringeo',
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'area_id'      => 7,
                'tipo_muestra' => 'Hisopado Uretral',
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'area_id'      => 7,
                'tipo_muestra' => 'Hisopado Vaginal',
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'area_id'      => 7,
                'tipo_muestra' => 'Otros',
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
        ];

        DB::table('area_tipo_muestras')->insert($data);
    }
}
