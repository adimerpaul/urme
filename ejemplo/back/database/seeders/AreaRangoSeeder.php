<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AreaRango;

class AreaRangoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Cambia este ID si el área HEMOGRAMA tiene otro id en tu tabla "areas"
        $areaIdHemograma = 1;

        $rangos = [
            // ==========================
            // HEMOGRAMA BÁSICO
            // ==========================
            [
                'area_id'        => $areaIdHemograma,
                'rango_nombre'   => 'Glóbulos Rojos',
                'rango_minimo'   => 4.2,
                'rango_maximo'   => 5.4,
                'unidad'         => 'X10⁶/µL',
                'interpretacion' => '4.2 - 5.4 X10⁶/µL',
            ],
            [
                'area_id'        => $areaIdHemograma,
                'rango_nombre'   => 'Glóbulos Blancos (Leucocitos)',
                'rango_minimo'   => 4.0,
                'rango_maximo'   => 10.0,
                'unidad'         => 'X10³/µL',
                'interpretacion' => '4.0 - 10.0 X10³/µL',
            ],
            [
                'area_id'        => $areaIdHemograma,
                'rango_nombre'   => 'Plaquetas',
                'rango_minimo'   => 150,
                'rango_maximo'   => 450,
                'unidad'         => 'X10e9/L',
                'interpretacion' => '150 a 450 X10e9/L',
            ],
            [
                'area_id'        => $areaIdHemograma,
                'rango_nombre'   => 'Hemoglobina',
                'rango_minimo'   => 15.7,
                'rango_maximo'   => 18.4,
                'unidad'         => 'g/dL',
                'interpretacion' => 'Varón 21-60: 16.9-18.4 g/dL; Mujer 21-60: 15.7-17.4 g/dL',
            ],
            [
                'area_id'        => $areaIdHemograma,
                'rango_nombre'   => 'Hematocrito',
                'rango_minimo'   => 0.44,  // mínimo global (mujer)
                'rango_maximo'   => 0.57,  // máximo global (varón)
                'unidad'         => 'L/L',
                'interpretacion' => 'Varón 21-60 años: 0,49-0,57 L/L; Mujer 21-60 años: 0,44-0,53 L/L',
            ],

            // ==========================
            // RECUENTO DIFERENCIAL
            // (uso normalmente el rango ABSOLUTO;
            // si solo hay %, uso el %)
            // ==========================
            [
                'area_id'        => $areaIdHemograma,
                'rango_nombre'   => 'Basófilos',
                'rango_minimo'   => 0.0,
                'rango_maximo'   => 0.15,
                'unidad'         => 'X10e9/L',
                'interpretacion' => '0 a 1 %; 0 a 0,15 X10e9/L',
            ],
            [
                'area_id'        => $areaIdHemograma,
                'rango_nombre'   => 'Eosinófilos',
                'rango_minimo'   => 0.03,
                'rango_maximo'   => 0.55,
                'unidad'         => 'X10e9/L',
                'interpretacion' => '0 a 4 %; 0,03 a 0,55 X10e9/L',
            ],
            [
                'area_id'        => $areaIdHemograma,
                'rango_nombre'   => 'Cayados',
                'rango_minimo'   => 0.0,
                'rango_maximo'   => 3.0,
                'unidad'         => '%',
                'interpretacion' => '0 a 3 %',
            ],
            [
                'area_id'        => $areaIdHemograma,
                'rango_nombre'   => 'Segmentados',
                'rango_minimo'   => 1.5,
                'rango_maximo'   => 8.0,
                'unidad'         => 'X10e9/L',
                'interpretacion' => '55 a 65 %; 1,5 a 8,0 X10e9/L',
            ],
            [
                'area_id'        => $areaIdHemograma,
                'rango_nombre'   => 'Linfocitos',
                'rango_minimo'   => 1.0,
                'rango_maximo'   => 4.0,
                'unidad'         => 'X10e9/L',
                'interpretacion' => '25 a 35 %; 1,0 a 4,0 X10e9/L',
            ],
            [
                'area_id'        => $areaIdHemograma,
                'rango_nombre'   => 'Monocitos',
                'rango_minimo'   => 0.05,
                'rango_maximo'   => 0.9,
                'unidad'         => 'X10e9/L',
                'interpretacion' => '2 a 6 %; 0,05 a 0,9 X10e9/L',
            ],
            [
                'area_id'        => $areaIdHemograma,
                'rango_nombre'   => 'BLASTOS',
                'rango_minimo'   => null,
                'rango_maximo'   => null,
                'unidad'         => 'X10e9/L',
                'interpretacion' => null,   // sin rango definido en la hoja
            ],
            [
                'area_id'        => $areaIdHemograma,
                'rango_nombre'   => 'METAMIELOCITO',
                'rango_minimo'   => null,
                'rango_maximo'   => null,
                'unidad'         => 'X10e9/L',
                'interpretacion' => null,
            ],
            [
                'area_id'        => $areaIdHemograma,
                'rango_nombre'   => 'ERITROBLASTOS',
                'rango_minimo'   => null,
                'rango_maximo'   => null,
                'unidad'         => 'X10e9/L',
                'interpretacion' => null,
            ],
            [
                'area_id'        => $areaIdHemograma,
                'rango_nombre'   => 'Leucocitos totales',
                'rango_minimo'   => null,
                'rango_maximo'   => null,
                'unidad'         => 'X10e9/L',
                'interpretacion' => null, // en la hoja solo aparece el total medido
            ],

            // ==========================
            // ÍNDICES HEMATIMÉTRICOS
            // ==========================
            [
                'area_id'        => $areaIdHemograma,
                'rango_nombre'   => 'V.C.M.',
                'rango_minimo'   => 83,   // 90 - 7
                'rango_maximo'   => 97,   // 90 + 7
                'unidad'         => 'fL',
                'interpretacion' => '90 +/- 7 fL',
            ],
            [
                'area_id'        => $areaIdHemograma,
                'rango_nombre'   => 'Hb.C.M.',
                'rango_minimo'   => 27,   // 29 - 2
                'rango_maximo'   => 31,   // 29 + 2
                'unidad'         => 'pg',
                'interpretacion' => '29 +/- 2 pg',
            ],
            [
                'area_id'        => $areaIdHemograma,
                'rango_nombre'   => 'CHCM',
                'rango_minimo'   => 33,
                'rango_maximo'   => 36,
                'unidad'         => 'g/dL',
                'interpretacion' => '33 - 36 g/dL',
            ],
            [
                'area_id'        => $areaIdHemograma,
                'rango_nombre'   => 'Grupo sanguíneo',
                'rango_minimo'   => null,
                'rango_maximo'   => null,
                'unidad'         => null,
                'interpretacion' => 'Grupo y factor RH (ej.: Grupo O, Factor RH Positivo)',
            ],

            // ==========================
            // COAGULOGRAMA
            // ==========================
            [
                'area_id'        => $areaIdHemograma,
                'rango_nombre'   => 'Tiempo de protrombina',
                'rango_minimo'   => 12,
                'rango_maximo'   => 12,
                'unidad'         => 'Seg',
                'interpretacion' => '12 Seg',
            ],
            [
                'area_id'        => $areaIdHemograma,
                'rango_nombre'   => 'Actividad de protrombina',
                'rango_minimo'   => 100,
                'rango_maximo'   => 100,
                'unidad'         => '%',
                'interpretacion' => '100 %',
            ],
            [
                'area_id'        => $areaIdHemograma,
                'rango_nombre'   => 'INR',
                'rango_minimo'   => 1.0,
                'rango_maximo'   => 1.0,
                'unidad'         => null,
                'interpretacion' => '1,00',
            ],
            [
                'area_id'        => $areaIdHemograma,
                'rango_nombre'   => 'ATTP',
                'rango_minimo'   => 20,
                'rango_maximo'   => 39,
                'unidad'         => 'Seg',
                'interpretacion' => '20-39 Seg',
            ],
            [
                'area_id'        => $areaIdHemograma,
                'rango_nombre'   => 'VES',
                'rango_minimo'   => 0,
                'rango_maximo'   => 20,
                'unidad'         => 'mm/hora',
                'interpretacion' => '0-20 mm/HORA',
            ],

            // ==========================
            // RETICULOCITOS / IPR
            // ==========================
//            [
//                'area_id'        => $areaIdHemograma,
//                'rango_nombre'   => 'Reticulocitos',
//                'rango_minimo'   => 0.5,
//                'rango_maximo'   => 2.5,
//                'unidad'         => '%',
//                'interpretacion' => '0,5% - 2,5%',
//            ],
            [
                'area_id'        => $areaIdHemograma,
                'rango_nombre'   => 'IPR',
                'rango_minimo'   => 0.5,
                'rango_maximo'   => 2.5,
                'unidad'         => '%',
                'interpretacion' => '0,5% - 1,5%',
            ],
            [
                'area_id'        => $areaIdHemograma,
                'rango_nombre'   => 'IPR2',
                'rango_minimo'   => 0.5,
                'rango_maximo'   => 1.5,
                'unidad'         => '%',
                'interpretacion' => '0,5% - 1,5%',
            ],
            [
                'area_id'        => $areaIdHemograma,
                'rango_nombre'   => 'FIBRINOGENO',
                'rango_minimo'   => 200,
                'rango_maximo'   => 400,
                'unidad'         => 'mg/dl',
                'interpretacion' => '200 - 400 mg/dl',
            ],
            [
                'area_id'        => $areaIdHemograma,
                'rango_nombre'   => 'Dimeros D',
                'rango_minimo'   => 0,
                'rango_maximo'   => 0.40,
                'unidad'         => 'ug/ml',
                'interpretacion' => '0 - 0.40 ug/ml',
            ],
            [
                'area_id'        => $areaIdHemograma,
                'rango_nombre'   => 'Reticulocitos',
                'rango_minimo'   => 0.5,
                'rango_maximo'   => 2.5,
                'unidad'         => '%',
                'interpretacion' => '0.5 - 2.5 %',
            ],
            [
                'area_id'        => $areaIdHemograma,
                'rango_nombre'   => 'RC',
                'rango_minimo'   => null,
                'rango_maximo'   => null,
                'unidad'         => '%',
                'interpretacion' => null,
            ],
        ];

        foreach ($rangos as $rango) {
            AreaRango::create($rango);
        }
    }
}
