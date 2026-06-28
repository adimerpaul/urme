<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AreaRango;

class AreaRangoMicrobiologiaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Cambia este ID si el área HEMOGRAMA tiene otro id en tu tabla "areas"
        $areaIdHemograma = 4;

//        $rangos = [
//            // ==========================
//            // HEMOGRAMA BÁSICO
//            // ==========================
//            [
//                'area_id'        => $areaIdHemograma,
//                'rango_nombre'   => 'Glóbulos Rojos',
//                'rango_minimo'   => 4.2,
//                'rango_maximo'   => 5.4,
//                'unidad'         => 'X10e12/L',
//                'interpretacion' => '4,2 a 5,4 X10e12/L',
//            ],
//            [
//                'area_id'        => $areaIdHemograma,
//                'rango_nombre'   => 'Glóbulos Blancos',
//                'rango_minimo'   => 4000,
//                'rango_maximo'   => 10000,
//                'unidad'         => 'X10e9/L',
//                'interpretacion' => '4000 a 10000 / mm3',
//            ],
//            [
//                'area_id'        => $areaIdHemograma,
//                'rango_nombre'   => 'Plaquetas',
//                'rango_minimo'   => 150,
//                'rango_maximo'   => 450,
//                'unidad'         => 'X10e9/L',
//                'interpretacion' => '150 a 450 X10e9/L',
//            ],
//            [
//                'area_id'        => $areaIdHemograma,
//                'rango_nombre'   => 'Hemoglobina',
//                'rango_minimo'   => 157,   // mínimo global (mujer)
//                'rango_maximo'   => 184,   // máximo global (varón)
//                'unidad'         => 'g/L',
//                'interpretacion' => 'Varón 21-60 años: 169-184 g/L; Mujer 21-60 años: 157-174 g/L',
//            ],
//            [
//                'area_id'        => $areaIdHemograma,
//                'rango_nombre'   => 'Hematocrito',
//                'rango_minimo'   => 0.44,  // mínimo global (mujer)
//                'rango_maximo'   => 0.57,  // máximo global (varón)
//                'unidad'         => 'L/L',
//                'interpretacion' => 'Varón 21-60 años: 0,49-0,57 L/L; Mujer 21-60 años: 0,44-0,53 L/L',
//            ],
//
//            // ==========================
//            // RECUENTO DIFERENCIAL
//            // (uso normalmente el rango ABSOLUTO;
//            // si solo hay %, uso el %)
//            // ==========================
//            [
//                'area_id'        => $areaIdHemograma,
//                'rango_nombre'   => 'Basófilos',
//                'rango_minimo'   => 0.0,
//                'rango_maximo'   => 0.15,
//                'unidad'         => 'X10e9/L',
//                'interpretacion' => '0 a 1 %; 0 a 0,15 X10e9/L',
//            ],
//            [
//                'area_id'        => $areaIdHemograma,
//                'rango_nombre'   => 'Eosinófilos',
//                'rango_minimo'   => 0.03,
//                'rango_maximo'   => 0.55,
//                'unidad'         => 'X10e9/L',
//                'interpretacion' => '0 a 4 %; 0,03 a 0,55 X10e9/L',
//            ],
//            [
//                'area_id'        => $areaIdHemograma,
//                'rango_nombre'   => 'Cayados',
//                'rango_minimo'   => 0.0,
//                'rango_maximo'   => 3.0,
//                'unidad'         => '%',
//                'interpretacion' => '0 a 3 %',
//            ],
//            [
//                'area_id'        => $areaIdHemograma,
//                'rango_nombre'   => 'Segmentados',
//                'rango_minimo'   => 1.5,
//                'rango_maximo'   => 8.0,
//                'unidad'         => 'X10e9/L',
//                'interpretacion' => '55 a 65 %; 1,5 a 8,0 X10e9/L',
//            ],
//            [
//                'area_id'        => $areaIdHemograma,
//                'rango_nombre'   => 'Linfocitos',
//                'rango_minimo'   => 1.0,
//                'rango_maximo'   => 4.0,
//                'unidad'         => 'X10e9/L',
//                'interpretacion' => '25 a 35 %; 1,0 a 4,0 X10e9/L',
//            ],
//            [
//                'area_id'        => $areaIdHemograma,
//                'rango_nombre'   => 'Monocitos',
//                'rango_minimo'   => 0.05,
//                'rango_maximo'   => 0.9,
//                'unidad'         => 'X10e9/L',
//                'interpretacion' => '2 a 6 %; 0,05 a 0,9 X10e9/L',
//            ],
//            [
//                'area_id'        => $areaIdHemograma,
//                'rango_nombre'   => 'BLASTOS',
//                'rango_minimo'   => null,
//                'rango_maximo'   => null,
//                'unidad'         => 'X10e9/L',
//                'interpretacion' => null,   // sin rango definido en la hoja
//            ],
//            [
//                'area_id'        => $areaIdHemograma,
//                'rango_nombre'   => 'METAMIELOCITO',
//                'rango_minimo'   => null,
//                'rango_maximo'   => null,
//                'unidad'         => 'X10e9/L',
//                'interpretacion' => null,
//            ],
//            [
//                'area_id'        => $areaIdHemograma,
//                'rango_nombre'   => 'ERITROBLASTOS',
//                'rango_minimo'   => null,
//                'rango_maximo'   => null,
//                'unidad'         => 'X10e9/L',
//                'interpretacion' => null,
//            ],
//            [
//                'area_id'        => $areaIdHemograma,
//                'rango_nombre'   => 'Leucocitos totales',
//                'rango_minimo'   => null,
//                'rango_maximo'   => null,
//                'unidad'         => 'X10e9/L',
//                'interpretacion' => null, // en la hoja solo aparece el total medido
//            ],
//
//            // ==========================
//            // ÍNDICES HEMATIMÉTRICOS
//            // ==========================
//            [
//                'area_id'        => $areaIdHemograma,
//                'rango_nombre'   => 'V.C.M.',
//                'rango_minimo'   => 83,   // 90 - 7
//                'rango_maximo'   => 97,   // 90 + 7
//                'unidad'         => 'fL',
//                'interpretacion' => '90 +/- 7 fL',
//            ],
//            [
//                'area_id'        => $areaIdHemograma,
//                'rango_nombre'   => 'Hb.C.M.',
//                'rango_minimo'   => 27,   // 29 - 2
//                'rango_maximo'   => 31,   // 29 + 2
//                'unidad'         => 'pg',
//                'interpretacion' => '29 +/- 2 pg',
//            ],
//            [
//                'area_id'        => $areaIdHemograma,
//                'rango_nombre'   => 'CHCM',
//                'rango_minimo'   => 338,  // 340 - 2
//                'rango_maximo'   => 342,  // 340 + 2
//                'unidad'         => 'g/L',
//                'interpretacion' => '340 +/- 2 g/L',
//            ],
//            [
//                'area_id'        => $areaIdHemograma,
//                'rango_nombre'   => 'Grupo sanguíneo',
//                'rango_minimo'   => null,
//                'rango_maximo'   => null,
//                'unidad'         => null,
//                'interpretacion' => 'Grupo y factor RH (ej.: Grupo O, Factor RH Positivo)',
//            ],
//
//            // ==========================
//            // COAGULOGRAMA
//            // ==========================
//            [
//                'area_id'        => $areaIdHemograma,
//                'rango_nombre'   => 'Tiempo de protrombina',
//                'rango_minimo'   => 12,
//                'rango_maximo'   => 12,
//                'unidad'         => 'Seg',
//                'interpretacion' => '12 Seg',
//            ],
//            [
//                'area_id'        => $areaIdHemograma,
//                'rango_nombre'   => 'Actividad de protrombina',
//                'rango_minimo'   => 100,
//                'rango_maximo'   => 100,
//                'unidad'         => '%',
//                'interpretacion' => '100 %',
//            ],
//            [
//                'area_id'        => $areaIdHemograma,
//                'rango_nombre'   => 'INR',
//                'rango_minimo'   => 1.0,
//                'rango_maximo'   => 1.0,
//                'unidad'         => null,
//                'interpretacion' => '1,00',
//            ],
//            [
//                'area_id'        => $areaIdHemograma,
//                'rango_nombre'   => 'ATTP',
//                'rango_minimo'   => 20,
//                'rango_maximo'   => 39,
//                'unidad'         => 'Seg',
//                'interpretacion' => '20-39 Seg',
//            ],
//            [
//                'area_id'        => $areaIdHemograma,
//                'rango_nombre'   => 'VES',
//                'rango_minimo'   => 0,
//                'rango_maximo'   => 20,
//                'unidad'         => 'mm/hora',
//                'interpretacion' => '0-20 mm/HORA',
//            ],
//
//            // ==========================
//            // RETICULOCITOS / IPR
//            // ==========================
////            [
////                'area_id'        => $areaIdHemograma,
////                'rango_nombre'   => 'Reticulocitos',
////                'rango_minimo'   => 0.5,
////                'rango_maximo'   => 2.5,
////                'unidad'         => '%',
////                'interpretacion' => '0,5% - 2,5%',
////            ],
//            [
//                'area_id'        => $areaIdHemograma,
//                'rango_nombre'   => 'IPR',
//                'rango_minimo'   => 0.5,
//                'rango_maximo'   => 2.5,
//                'unidad'         => '%',
//                'interpretacion' => '0,5% - 1,5%',
//            ],
//            [
//                'area_id'        => $areaIdHemograma,
//                'rango_nombre'   => 'IPR2',
//                'rango_minimo'   => 0.5,
//                'rango_maximo'   => 1.5,
//                'unidad'         => '%',
//                'interpretacion' => '0,5% - 1,5%',
//            ],
//            [
//                'area_id'        => $areaIdHemograma,
//                'rango_nombre'   => 'FIBRINOGENO',
//                'rango_minimo'   => 2,
//                'rango_maximo'   => 4,
//                'unidad'         => 'g/L',
//                'interpretacion' => '2 a 4 g/L',
//            ],
//        ];
//        INSERT INTO sil.area_rangos
//    (id, area_id, rango_nombre, rango_minimo, rango_maximo, unidad, interpretacion, deleted_at, created_at, updated_at)
//VALUES(134, 4, 'Cefepime', NULL, NULL, NULL, NULL, NULL, '2025-12-06 06:06:38.000', '2025-12-06 06:06:38.000');
//INSERT INTO sil.area_rangos
//    (id, area_id, rango_nombre, rango_minimo, rango_maximo, unidad, interpretacion, deleted_at, created_at, updated_at)
//VALUES(133, 4, 'Meropenem', NULL, NULL, NULL, NULL, NULL, '2025-12-06 06:06:30.000', '2025-12-06 06:06:30.000');
//INSERT INTO sil.area_rangos
//    (id, area_id, rango_nombre, rango_minimo, rango_maximo, unidad, interpretacion, deleted_at, created_at, updated_at)
//VALUES(132, 4, 'Levofloxacina', NULL, NULL, NULL, NULL, NULL, '2025-12-06 06:06:22.000', '2025-12-06 06:06:22.000');
//INSERT INTO sil.area_rangos
//    (id, area_id, rango_nombre, rango_minimo, rango_maximo, unidad, interpretacion, deleted_at, created_at, updated_at)
//VALUES(131, 4, 'Fosfomicina', NULL, NULL, NULL, NULL, NULL, '2025-12-06 06:06:13.000', '2025-12-06 06:06:13.000');
//INSERT INTO sil.area_rangos
//    (id, area_id, rango_nombre, rango_minimo, rango_maximo, unidad, interpretacion, deleted_at, created_at, updated_at)
//VALUES(130, 4, 'Tazobactam Piperacilina', NULL, NULL, NULL, NULL, NULL, '2025-12-06 06:06:03.000', '2025-12-06 06:06:03.000');
//INSERT INTO sil.area_rangos
//    (id, area_id, rango_nombre, rango_minimo, rango_maximo, unidad, interpretacion, deleted_at, created_at, updated_at)
//VALUES(129, 4, 'Vancomicina 30 mg', NULL, NULL, NULL, NULL, NULL, '2025-12-06 06:05:54.000', '2025-12-06 06:05:54.000');
//INSERT INTO sil.area_rangos
//    (id, area_id, rango_nombre, rango_minimo, rango_maximo, unidad, interpretacion, deleted_at, created_at, updated_at)
//VALUES(128, 4, 'Trimet/Sulfametox 25 mg', NULL, NULL, NULL, NULL, NULL, '2025-12-06 06:05:44.000', '2025-12-06 06:05:44.000');
//INSERT INTO sil.area_rangos
//    (id, area_id, rango_nombre, rango_minimo, rango_maximo, unidad, interpretacion, deleted_at, created_at, updated_at)
//VALUES(127, 4, 'Tetraciclina 30 mg', NULL, NULL, NULL, NULL, NULL, '2025-12-06 06:05:30.000', '2025-12-06 06:05:30.000');
//INSERT INTO sil.area_rangos
//    (id, area_id, rango_nombre, rango_minimo, rango_maximo, unidad, interpretacion, deleted_at, created_at, updated_at)
//VALUES(126, 4, 'Penicilina', NULL, NULL, NULL, NULL, NULL, '2025-12-06 06:05:20.000', '2025-12-06 06:05:20.000');
//INSERT INTO sil.area_rangos
//    (id, area_id, rango_nombre, rango_minimo, rango_maximo, unidad, interpretacion, deleted_at, created_at, updated_at)
//VALUES(125, 4, 'Oxacilina 1 mg', NULL, NULL, NULL, NULL, NULL, '2025-12-06 06:05:12.000', '2025-12-06 06:05:12.000');
//INSERT INTO sil.area_rangos
//    (id, area_id, rango_nombre, rango_minimo, rango_maximo, unidad, interpretacion, deleted_at, created_at, updated_at)
//VALUES(124, 4, 'Norfloxacina 10 mg', NULL, NULL, NULL, NULL, NULL, '2025-12-06 06:05:00.000', '2025-12-06 06:05:00.000');
//INSERT INTO sil.area_rangos
//    (id, area_id, rango_nombre, rango_minimo, rango_maximo, unidad, interpretacion, deleted_at, created_at, updated_at)
//VALUES(123, 4, 'Nitrofurantoína 300 mg', NULL, NULL, NULL, NULL, NULL, '2025-12-06 06:04:25.000', '2025-12-06 06:04:25.000');
//INSERT INTO sil.area_rangos
//    (id, area_id, rango_nombre, rango_minimo, rango_maximo, unidad, interpretacion, deleted_at, created_at, updated_at)
//VALUES(122, 4, 'Imipenem 10 mg', NULL, NULL, NULL, NULL, NULL, '2025-12-06 06:04:08.000', '2025-12-06 06:04:08.000');
//INSERT INTO sil.area_rangos
//    (id, area_id, rango_nombre, rango_minimo, rango_maximo, unidad, interpretacion, deleted_at, created_at, updated_at)
//VALUES(121, 4, 'Gentamicina 120 mg', NULL, NULL, NULL, NULL, NULL, '2025-12-06 06:03:56.000', '2025-12-06 06:03:56.000');
//INSERT INTO sil.area_rangos
//    (id, area_id, rango_nombre, rango_minimo, rango_maximo, unidad, interpretacion, deleted_at, created_at, updated_at)
//VALUES(120, 4, 'Gentamicina 10 mg', NULL, NULL, NULL, NULL, NULL, '2025-12-06 06:03:46.000', '2025-12-06 06:03:46.000');
//INSERT INTO sil.area_rangos
//    (id, area_id, rango_nombre, rango_minimo, rango_maximo, unidad, interpretacion, deleted_at, created_at, updated_at)
//VALUES(119, 4, 'Eritromicina 15 mg', NULL, NULL, NULL, NULL, NULL, '2025-12-06 06:03:35.000', '2025-12-06 06:03:35.000');
//INSERT INTO sil.area_rangos
//    (id, area_id, rango_nombre, rango_minimo, rango_maximo, unidad, interpretacion, deleted_at, created_at, updated_at)
//VALUES(118, 4, 'Ciprofloxacina 5 mg', NULL, NULL, NULL, NULL, NULL, '2025-12-06 06:03:25.000', '2025-12-06 06:03:25.000');
//INSERT INTO sil.area_rangos
//    (id, area_id, rango_nombre, rango_minimo, rango_maximo, unidad, interpretacion, deleted_at, created_at, updated_at)
//VALUES(117, 4, 'Cloranfenicol 30 mg', NULL, NULL, NULL, NULL, NULL, '2025-12-06 06:03:14.000', '2025-12-06 06:03:14.000');
//INSERT INTO sil.area_rangos
//    (id, area_id, rango_nombre, rango_minimo, rango_maximo, unidad, interpretacion, deleted_at, created_at, updated_at)
//VALUES(116, 4, 'Cloxacilina', NULL, NULL, NULL, NULL, NULL, '2025-12-06 06:03:04.000', '2025-12-06 06:03:04.000');
//INSERT INTO sil.area_rangos
//    (id, area_id, rango_nombre, rango_minimo, rango_maximo, unidad, interpretacion, deleted_at, created_at, updated_at)
//VALUES(115, 4, 'Clindamicina 2 mg', NULL, NULL, NULL, NULL, NULL, '2025-12-06 06:02:55.000', '2025-12-06 06:02:55.000');
//INSERT INTO sil.area_rangos
//    (id, area_id, rango_nombre, rango_minimo, rango_maximo, unidad, interpretacion, deleted_at, created_at, updated_at)
//VALUES(114, 4, 'Cefalotina 30 mg', NULL, NULL, NULL, NULL, NULL, '2025-12-06 06:02:43.000', '2025-12-06 06:02:43.000');
//INSERT INTO sil.area_rangos
//    (id, area_id, rango_nombre, rango_minimo, rango_maximo, unidad, interpretacion, deleted_at, created_at, updated_at)
//VALUES(113, 4, 'Ceftriaxona 30 mg', NULL, NULL, NULL, NULL, NULL, '2025-12-06 06:02:32.000', '2025-12-06 06:02:32.000');
//INSERT INTO sil.area_rangos
//    (id, area_id, rango_nombre, rango_minimo, rango_maximo, unidad, interpretacion, deleted_at, created_at, updated_at)
//VALUES(112, 4, 'Ceftazidima 30 mg', NULL, NULL, NULL, NULL, NULL, '2025-12-06 06:02:20.000', '2025-12-06 06:02:20.000');
//INSERT INTO sil.area_rangos
//    (id, area_id, rango_nombre, rango_minimo, rango_maximo, unidad, interpretacion, deleted_at, created_at, updated_at)
//VALUES(111, 4, 'Cefoxitina', NULL, NULL, NULL, NULL, NULL, '2025-12-06 06:02:08.000', '2025-12-06 06:02:08.000');
//INSERT INTO sil.area_rangos
//    (id, area_id, rango_nombre, rango_minimo, rango_maximo, unidad, interpretacion, deleted_at, created_at, updated_at)
//VALUES(110, 4, 'Cefotaxima 30 mg', NULL, NULL, NULL, NULL, NULL, '2025-12-06 06:01:57.000', '2025-12-06 06:01:57.000');
//INSERT INTO sil.area_rangos
//    (id, area_id, rango_nombre, rango_minimo, rango_maximo, unidad, interpretacion, deleted_at, created_at, updated_at)
//VALUES(109, 4, 'Cefoperasona 75 mg', NULL, NULL, NULL, NULL, NULL, '2025-12-06 06:01:41.000', '2025-12-06 06:01:41.000');
//INSERT INTO sil.area_rangos
//    (id, area_id, rango_nombre, rango_minimo, rango_maximo, unidad, interpretacion, deleted_at, created_at, updated_at)
//VALUES(81, 4, 'Azitromicina', NULL, NULL, NULL, NULL, NULL, '2025-12-06 04:16:22.000', '2025-12-06 04:16:22.000');
//INSERT INTO sil.area_rangos
//    (id, area_id, rango_nombre, rango_minimo, rango_maximo, unidad, interpretacion, deleted_at, created_at, updated_at)
//VALUES(80, 4, 'Ampicilina 10 mg', NULL, NULL, NULL, NULL, NULL, '2025-12-06 04:16:10.000', '2025-12-06 05:34:24.000');
//INSERT INTO sil.area_rangos
//    (id, area_id, rango_nombre, rango_minimo, rango_maximo, unidad, interpretacion, deleted_at, created_at, updated_at)
//VALUES(79, 4, 'Amoxi/Clavulánico 20/10 mg', NULL, NULL, NULL, NULL, NULL, '2025-12-06 04:15:47.000', '2025-12-06 05:34:14.000');

        $rangos = [
            ['area_id' => $areaIdHemograma, 'rango_nombre' => 'Ampicilina 10 mg', 'rango_minimo' => null, 'rango_maximo' => null, 'unidad' => null, 'interpretacion' => null],
            ['area_id' => $areaIdHemograma, 'rango_nombre' => 'Amoxi/Clavulánico 20/10 mg', 'rango_minimo' => null, 'rango_maximo' => null, 'unidad' => null, 'interpretacion' => null],
            ['area_id' => $areaIdHemograma, 'rango_nombre' => 'Azitromicina', 'rango_minimo' => null, 'rango_maximo' => null, 'unidad' => null, 'interpretacion' => null],
            ['area_id' => $areaIdHemograma, 'rango_nombre' => 'Cefoperasona 75 mg', 'rango_minimo' => null, 'rango_maximo' => null, 'unidad' => null, 'interpretacion' => null],
            ['area_id' => $areaIdHemograma, 'rango_nombre' => 'Cefotaxima 30 mg', 'rango_minimo' => null, 'rango_maximo' => null, 'unidad' => null, 'interpretacion' => null],
            ['area_id' => $areaIdHemograma, 'rango_nombre' => 'Cefoxitina', 'rango_minimo' => null, 'rango_maximo' => null, 'unidad' => null, 'interpretacion' => null],
            ['area_id' => $areaIdHemograma, 'rango_nombre' => 'Ceftazidima 30 mg', 'rango_minimo' => null, 'rango_maximo' => null, 'unidad' => null, 'interpretacion' => null],
            ['area_id' => $areaIdHemograma, 'rango_nombre' => 'Ceftriaxona 30 mg', 'rango_minimo' => null, 'rango_maximo' => null, 'unidad' => null, 'interpretacion' => null],
            ['area_id' => $areaIdHemograma, 'rango_nombre' => 'Cefalotina 30 mg', 'rango_minimo' => null, 'rango_maximo' => null, 'unidad' => null, 'interpretacion' => null],
            ['area_id' => $areaIdHemograma, 'rango_nombre' => 'Clindamicina 2 mg', 'rango_minimo' => null, 'rango_maximo' => null, 'unidad' => null, 'interpretacion' => null],
            ['area_id' => $areaIdHemograma, 'rango_nombre' => 'Cloxacilina', 'rango_minimo' => null, 'rango_maximo' => null, 'unidad' => null, 'interpretacion' => null],
            ['area_id' => $areaIdHemograma, 'rango_nombre' => 'Cloranfenicol 30 mg', 'rango_minimo' => null, 'rango_maximo' => null, 'unidad' => null, 'interpretacion' => null],
            ['area_id' => $areaIdHemograma, 'rango_nombre' => 'Ciprofloxacina 5 mg', 'rango_minimo' => null, 'rango_maximo' => null, 'unidad' => null, 'interpretacion' => null],
            ['area_id' => $areaIdHemograma, 'rango_nombre' => 'Eritromicina 15 mg', 'rango_minimo' => null, 'rango_maximo' => null, 'unidad' => null, 'interpretacion' => null],
            ['area_id' => $areaIdHemograma, 'rango_nombre' => 'Gentamicina 10 mg', 'rango_minimo' => null, 'rango_maximo' => null, 'unidad' => null, 'interpretacion' => null],
            ['area_id' => $areaIdHemograma, 'rango_nombre' => 'Gentamicina 120 mg', 'rango_minimo' => null, 'rango_maximo' => null, 'unidad' => null, 'interpretacion' => null],
            ['area_id' => $areaIdHemograma, 'rango_nombre' => 'Imipenem 10 mg', 'rango_minimo' => null, 'rango_maximo' => null, 'unidad' => null, 'interpretacion' => null],
            ['area_id' => $areaIdHemograma, 'rango_nombre' => 'Nitrofurantoína 300 mg', 'rango_minimo' => null, 'rango_maximo' => null, 'unidad' => null, 'interpretacion' => null],
            ['area_id' => $areaIdHemograma, 'rango_nombre' => 'Norfloxacina 10 mg', 'rango_minimo' => null, 'rango_maximo' => null, 'unidad' => null, 'interpretacion' => null],
            ['area_id' => $areaIdHemograma, 'rango_nombre' => 'Oxacilina 1 mg', 'rango_minimo' => null, 'rango_maximo' => null, 'unidad' => null, 'interpretacion' => null],
            ['area_id' => $areaIdHemograma, 'rango_nombre' => 'Penicilina', 'rango_minimo' => null, 'rango_maximo' => null, 'unidad' => null, 'interpretacion' => null],
            ['area_id' => $areaIdHemograma, 'rango_nombre' => 'Tetraciclina 30 mg', 'rango_minimo' => null, 'rango_maximo' => null, 'unidad' => null, 'interpretacion' => null],
            ['area_id' => $areaIdHemograma, 'rango_nombre' => 'Trimet/Sulfametox 25 mg', 'rango_minimo' => null, 'rango_maximo' => null, 'unidad' => null, 'interpretacion' => null],
            ['area_id' => $areaIdHemograma, 'rango_nombre' => 'Vancomicina 30 mg', 'rango_minimo' => null, 'rango_maximo' => null, 'unidad' => null, 'interpretacion' => null],
            ['area_id' => $areaIdHemograma, 'rango_nombre' => 'Tazobactam Piperacilina', 'rango_minimo' => null, 'rango_maximo' => null, 'unidad' => null, 'interpretacion' => null],
            ['area_id' => $areaIdHemograma, 'rango_nombre' => 'Fosfomicina', 'rango_minimo' => null, 'rango_maximo' => null, 'unidad' => null, 'interpretacion' => null],
            ['area_id' => $areaIdHemograma, 'rango_nombre' => 'Levofloxacina', 'rango_minimo' => null, 'rango_maximo' => null, 'unidad' => null, 'interpretacion' => null],
            ['area_id' => $areaIdHemograma, 'rango_nombre' => 'Meropenem', 'rango_minimo' => null, 'rango_maximo' => null, 'unidad' => null, 'interpretacion' => null],
            ['area_id' => $areaIdHemograma, 'rango_nombre' => 'Cefepime', 'rango_minimo' => null, 'rango_maximo' => null, 'unidad' => null, 'interpretacion' => null],
        ];

        foreach ($rangos as $rango) {
            AreaRango::create($rango);
        }
    }
}
