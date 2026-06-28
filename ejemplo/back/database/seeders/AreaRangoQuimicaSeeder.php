<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AreaRango;

class AreaRangoQuimicaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ID del área "QUIMICA SANGUINEA" en la tabla areas
        $areaIdQuimica = 2;

        $rangos = [
            // ---------------------------
            // QUIMICA SANGUINEA / BIOQUIMICA
            // ---------------------------
            [
                'area_id'        => $areaIdQuimica,
                'rango_nombre'   => 'Ácido Úrico',
                'rango_minimo'   => 1.5,
                'rango_maximo'   => 7.0,
                'unidad'         => 'mg/dl',
                'interpretacion' => '1,5 - 7 mg/dl',
            ],
            [
                'area_id'        => $areaIdQuimica,
                'rango_nombre'   => 'Albúmina',
                'rango_minimo'   => 3.5,
                'rango_maximo'   => 5.3,
                'unidad'         => 'g/dl',
                'interpretacion' => '3,5 - 5,3 g/dl',
            ],
            [
                'area_id'        => $areaIdQuimica,
                'rango_nombre'   => 'Proteínas totales',
                'rango_minimo'   => 6.2,
                'rango_maximo'   => 8.5,
                'unidad'         => 'g/dl',
                'interpretacion' => '6,2 - 8,5 g/dl',
            ],
            [
                'area_id'        => $areaIdQuimica,
                'rango_nombre'   => 'Bilirrubina Total',
                'rango_minimo'   => null,
                'rango_maximo'   => 1.0,
                'unidad'         => 'mg/dl',
                'interpretacion' => 'Hasta 1,0 mg/dl',
            ],
            [
                'area_id'        => $areaIdQuimica,
                'rango_nombre'   => 'Bilirrubina Directa',
                'rango_minimo'   => null,
                'rango_maximo'   => 0.2,
                'unidad'         => 'mg/dl',
                'interpretacion' => 'Hasta 0,2 mg/dl',
            ],
            [
                'area_id'        => $areaIdQuimica,
                'rango_nombre'   => 'Bilirrubina Indirecta',
                'rango_minimo'   => null,
                'rango_maximo'   => 0.8,
                'unidad'         => 'mg/dl',
                'interpretacion' => 'Hasta 0,8 mg/dl',
            ],
            [
                'area_id'        => $areaIdQuimica,
                'rango_nombre'   => 'G.O.T. (TGO)',
                'rango_minimo'   => null,
                'rango_maximo'   => 37.0,
                'unidad'         => 'U/L',
                'interpretacion' => 'Hasta 37 U/L',
            ],
            [
                'area_id'        => $areaIdQuimica,
                'rango_nombre'   => 'G.P.T. (TGP)',
                'rango_minimo'   => null,
                'rango_maximo'   => 40.0,
                'unidad'         => 'U/L',
                'interpretacion' => 'Hasta 40 U/L',
            ],
            [
                'area_id'        => $areaIdQuimica,
                'rango_nombre'   => 'Fosfatasa Alcalina',
                'rango_minimo'   => null,
                'rango_maximo'   => 115.0,
                'unidad'         => 'UI/L',
                'interpretacion' => 'Hasta 115 UI/L',
            ],
            [
                'area_id'        => $areaIdQuimica,
                'rango_nombre'   => 'GGT',
                'rango_minimo'   => 6.0,
                'rango_maximo'   => 71.0,
                'unidad'         => 'U/L',
                'interpretacion' => '6 a 71 U/L',
            ],
            [
                'area_id'        => $areaIdQuimica,
                'rango_nombre'   => 'Amilasa',
                'rango_minimo'   => null,
                'rango_maximo'   => 90.0,
                'unidad'         => 'U/dl',
                'interpretacion' => 'Menor a 90 U/dl',
            ],
            [
                'area_id'        => $areaIdQuimica,
                'rango_nombre'   => 'Glucosa',
                'rango_minimo'   => 70.0,
                'rango_maximo'   => 110.0,
                'unidad'         => 'mg/dl',
                'interpretacion' => '70 - 110 mg/dl',
            ],
            [
                'area_id'        => $areaIdQuimica,
                'rango_nombre'   => 'Urea',
                'rango_minimo'   => 20.0,
                'rango_maximo'   => 45.0,
                'unidad'         => 'mg/dl',
                'interpretacion' => '20 - 45 mg/dl',
            ],
            // NUS: en la hoja solo se ve el resultado, no el rango
            [
                'area_id'        => $areaIdQuimica,
                'rango_nombre'   => 'NUS',
                'rango_minimo'   => null,
                'rango_maximo'   => null,
                'unidad'         => 'mg/dl',
                'interpretacion' => null,
            ],
            [
                'area_id'        => $areaIdQuimica,
                'rango_nombre'   => 'Creatinina',
                'rango_minimo'   => 0.7,   // M: 0,7-1,3   H: 0,9-1,5
                'rango_maximo'   => 1.5,
                'unidad'         => 'mg/dl',
                'interpretacion' => 'H: 0,9-1,5 mg/dl; M: 0,7-1,3 mg/dl',
            ],

            // ---------------------------
            // LIPIDOGRAMA
            // ---------------------------
            [
                'area_id'        => $areaIdQuimica,
                'rango_nombre'   => 'Triglicéridos',
                'rango_minimo'   => null,
                'rango_maximo'   => 150.0,
                'unidad'         => 'mg/dl',
                'interpretacion' => 'Menor a 150 mg/dl',
            ],
            [
                'area_id'        => $areaIdQuimica,
                'rango_nombre'   => 'Colesterol total',
                'rango_minimo'   => null,
                'rango_maximo'   => 200.0,
                'unidad'         => 'mg/dl',
                'interpretacion' => 'Menor a 200 mg/dl',
            ],
            [
                'area_id'        => $areaIdQuimica,
                'rango_nombre'   => 'HDL Colesterol',
                'rango_minimo'   => 40.0,
                'rango_maximo'   => null,
                'unidad'         => 'mg/dl',
                'interpretacion' => 'Mayor a 40 mg/dl',
            ],
            [
                'area_id'        => $areaIdQuimica,
                'rango_nombre'   => 'LDL Colesterol',
                'rango_minimo'   => null,
                'rango_maximo'   => 129.0,
                'unidad'         => 'mg/dl',
                'interpretacion' => 'Menor a 129 mg/dl',
            ],
            [
                'area_id'        => $areaIdQuimica,
                'rango_nombre'   => 'VLDL Colesterol',
                'rango_minimo'   => null,
                'rango_maximo'   => 150.0,
                'unidad'         => 'mg/dl',
                'interpretacion' => 'Menor a 150 mg/dl',
            ],

            // ---------------------------
            // ENZIMAS MUSCULARES
            // ---------------------------
            [
                'area_id'        => $areaIdQuimica,
                'rango_nombre'   => 'CK total',
                'rango_minimo'   => 26.0,
                'rango_maximo'   => 174.0,
                'unidad'         => 'U/L',
                'interpretacion' => '26 a 174 U/L',
            ],
            [
                'area_id'        => $areaIdQuimica,
                'rango_nombre'   => 'CK-MB',
                'rango_minimo'   => null,
                'rango_maximo'   => 25.0,
                'unidad'         => 'UI/L (37°C)',
                'interpretacion' => 'Hasta 25 UI/L (37°C)',
            ],

            // ---------------------------
            // OTROS
            // ---------------------------
            [
                'area_id'        => $areaIdQuimica,
                'rango_nombre'   => 'Ferritina',
                'rango_minimo'   => null,
                'rango_maximo'   => null,
                'unidad'         => null,
                'interpretacion' => null,
            ],
            [
                'area_id'        => $areaIdQuimica,
                'rango_nombre'   => 'Hierro sérico',
                'rango_minimo'   => 50.0,
                'rango_maximo'   => 170.0,
                'unidad'         => 'ug/dl',
                'interpretacion' => '50 - 170 ug/dl',
            ],
            [
                'area_id'        => $areaIdQuimica,
                'rango_nombre'   => 'G.O.T. cinético',
                'rango_minimo'   => null,
                'rango_maximo'   => 12.0,
                'unidad'         => 'U/L',
                'interpretacion' => 'Hasta 12 U/L',
            ],
            [
                'area_id'        => $areaIdQuimica,
                'rango_nombre'   => 'G.P.T. cinético',
                'rango_minimo'   => null,
                'rango_maximo'   => 12.0,
                'unidad'         => 'U/L',
                'interpretacion' => 'Hasta 12 U/L',
            ],
            [
                'area_id'        => $areaIdQuimica,
                'rango_nombre'   => 'Hb glicosilada',
                'rango_minimo'   => 3.8,
                'rango_maximo'   => 5.8,
                'unidad'         => '%',
                'interpretacion' => '3,8 - 5,8 %',
            ],
            [
                'area_id'        => $areaIdQuimica,
                'rango_nombre'   => 'Hb A1C',
                'rango_minimo'   => null,
                'rango_maximo'   => null,
                'unidad'         => null,
                'interpretacion' => null,
            ],

            //  ELECTROLITOS Y MINERALES
            [
                'area_id'        => $areaIdQuimica,
                'rango_nombre'   => 'Sodio',
                'rango_minimo'   => 135.0,
                'rango_maximo'   => 148.0,
                'unidad'         => 'mmol/L',
                'interpretacion' => '135 - 148 mmol/L',
            ],
            [
                'area_id'        => $areaIdQuimica,
                'rango_nombre'   => 'Potasio',
                'rango_minimo'   => 3.5,
                'rango_maximo'   => 5.3,
                'unidad'         => 'mmol/L',
                'interpretacion' => '3,5 - 5,3 mmol/L',
            ],
            [
                'area_id'        => $areaIdQuimica,
                'rango_nombre'   => 'Cloro',
                'rango_minimo'   => 98.0,
                'rango_maximo'   => 107.0,
                'unidad'         => 'mEq/L',
                'interpretacion' => '98 - 107 mEq/L',
            ],
            [
                'area_id'        => $areaIdQuimica,
                'rango_nombre'   => 'Calcio',
                'rango_minimo'   => 8.6,
                'rango_maximo'   => 10.3,
                'unidad'         => 'mg/dl',
                'interpretacion' => '8,6 - 10,3 mg/dl',
            ],
            [
                'area_id'        => $areaIdQuimica,
                'rango_nombre'   => 'Fósforo',
                'rango_minimo'   => 2.7,
                'rango_maximo'   => 4.5,
                'unidad'         => 'mg/dl',
                'interpretacion' => '2,7 - 4,5 mg/dl',
            ],
            [
                'area_id'        => $areaIdQuimica,
                'rango_nombre'   => 'Magnesio',
                'rango_minimo'   => 1.58,
                'rango_maximo'   => 2.56,
                'unidad'         => 'mg/dl',
                'interpretacion' => '1,58 - 2,56 mg/dl',
            ],
            [
                'area_id'        => $areaIdQuimica,
                'rango_nombre'   => 'LDH',
                'rango_minimo'   => 200.0,   // mínimo global (M: 200-480; N: 490-730)
                'rango_maximo'   => 730.0,
                'unidad'         => 'U/L',
                'interpretacion' => 'N: 490-730 U/L; M: 200-480 U/L',
            ],

            // ---------------------------
            // MICROALBUMINURIA
            // ---------------------------
            [
                'area_id'        => $areaIdQuimica,
                'rango_nombre'   => 'Microalbuminuria',
                'rango_minimo'   => null,
                'rango_maximo'   => 30.0,
                'unidad'         => 'mg/L',
                'interpretacion' => 'Normal: < 30 mg/L',
            ],

            // 24 HORAS
            [
                'area_id'        => $areaIdQuimica,
                'rango_nombre'   => 'Creatinuria 24 hrs.',
                'rango_minimo'   => null,
                'rango_maximo'   => null,
                'unidad'         => 'g/24hrs',
                'interpretacion' => null,
            ],
            [
                'area_id'        => $areaIdQuimica,
                'rango_nombre'   => 'Proteinuria de 24 hrs.',
                'rango_minimo'   => 28.0,
                'rango_maximo'   => 141.0,
                'unidad'         => 'mg/24hrs',
                'interpretacion' => '28 - 141 mg/24hrs',
            ],

            // ---------------------------
            // SEROLOGÍA / MATERNIDAD
            // ---------------------------
            [
                'area_id'        => $areaIdQuimica,
                'rango_nombre'   => 'ASO',
                'rango_minimo'   => null,
                'rango_maximo'   => 200.0,
                'unidad'         => null,
                'interpretacion' => '< 200',
            ],
            [
                'area_id'        => $areaIdQuimica,
                'rango_nombre'   => 'FR',
                'rango_minimo'   => null,
                'rango_maximo'   => 8.0,
                'unidad'         => 'UI/mL',
                'interpretacion' => '< 8 UI/mL',
            ],
            [
                'area_id'        => $areaIdQuimica,
                'rango_nombre'   => 'PCR',
                'rango_minimo'   => null,
                'rango_maximo'   => 8.0,
                'unidad'         => 'mg/L',
                'interpretacion' => '< 8 mg/L',
            ],
            [
                'area_id'        => $areaIdQuimica,
                'rango_nombre'   => 'Prueba rápida de VIH',
                'rango_minimo'   => null,
                'rango_maximo'   => null,
                'unidad'         => null,
                'interpretacion' => 'No reactivo',
            ],
            [
                'area_id'        => $areaIdQuimica,
                'rango_nombre'   => 'RPR',
                'rango_minimo'   => null,
                'rango_maximo'   => null,
                'unidad'         => null,
                'interpretacion' => 'No reactivo',
            ],
            [
                'area_id'        => $areaIdQuimica,
                'rango_nombre'   => 'Reacción de Widal',
                'rango_minimo'   => null,
                'rango_maximo'   => null,
                'unidad'         => null,
                'interpretacion' => 'O: Negativo; H: Negativo; A: Negativo; B: Negativo',
            ],
            [
                'area_id'        => $areaIdQuimica,
                'rango_nombre'   => 'D.C.E.',
                'rango_minimo'   => null,
                'rango_maximo'   => null,
                'unidad'         => null,
                'interpretacion' => null,
            ],
        ];

        foreach ($rangos as $rango) {
            AreaRango::create($rango);
        }
    }
}
