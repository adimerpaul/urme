<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\AreaRango;
use App\Models\PerfilImpresion;
use App\Models\PerfilImpresionItem;

class PerfilImpresionSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            $areaHemato  = 1; // ID área Hematología
            $areaQuimica = 2; // ID área Química Sanguínea / Serología

            /* =========================================================
             * 1) PERFILES
             * ======================================================= */

            $hemograma = PerfilImpresion::firstOrCreate(
                ['codigo' => 'HEMOGRAMA'],
                ['nombre' => 'HEMOGRAMA']
            );

            $quimica = PerfilImpresion::firstOrCreate(
                ['codigo' => 'QUIMICA'],
                ['nombre' => 'QUÍMICA SANGUÍNEA Y SEROLOGÍA']
            );

            /* =========================================================
             * Helpers
             * ======================================================= */

            $crearRango = function (
                int $areaId,
                string $nombre,
                ?string $unidad,
                ?float $min,
                ?float $max
            ) {
                return AreaRango::firstOrCreate(
                    [
                        'area_id'      => $areaId,
                        'rango_nombre' => $nombre,
                    ],
                    [
                        'unidad'       => $unidad,
                        'rango_minimo' => $min,
                        'rango_maximo' => $max,
                    ]
                );
            };

            $crearItem = function (
                PerfilImpresion $perfil,
                AreaRango $rango,
                string $columna,
                int $orden,
                ?string $seccion = null,
                bool $mostrar = true
            ) {
                return PerfilImpresionItem::firstOrCreate(
                    [
                        'perfil_id'     => $perfil->id,
                        'area_rango_id' => $rango->id,
                    ],
                    [
                        'seccion'             => $seccion,
                        'columna'             => $columna, // 'IZQ' o 'DER'
                        'orden'               => $orden,
                        'mostrar_en_paciente' => $mostrar,
                    ]
                );
            };

            /* =========================================================
             * 2) HEMOGRAMA  (Área 1)
             * ======================================================= */

            // --- COLUMNA IZQUIERDA: parámetros principales + recuento diferencial
            $hemogramaIzq = [
                // sección HEMOGRAMA principal
                ['Glóbulos Rojos',   'x10^12/L', 4.2,   5.4,   null],
                ['Glóbulos Blancos', '/mm³',     4000,  10000, null],
                ['Plaquetas',        'x10^9/L',  150,   450,   null],
                ['Hemoglobina',      'g/dL',     null,  null,  null],
                ['Hematocrito',      'L/L',      null,  null,  null],

                // sección RECUENTO DIFERENCIAL (%)
                ['Basófilos',        '%',        0.0,   1.0,   'RECUENTO DIFERENCIAL'],
                ['Eosinófilos',      '%',        0.0,   5.5,   'RECUENTO DIFERENCIAL'],
                ['Cayados',          '%',        0.0,   5.0,   'RECUENTO DIFERENCIAL'],
                ['Segmentados',      '%',        45.0,  85.0,  'RECUENTO DIFERENCIAL'],
                ['Linfocitos',       '%',        25.0,  35.0,  'RECUENTO DIFERENCIAL'],
                ['Monocitos',        '%',        2.0,   6.0,   'RECUENTO DIFERENCIAL'],
                ['BLASTOS',          '%',        0.0,   0.0,   'RECUENTO DIFERENCIAL'],
                ['METAMIELOCITO',    '%',        0.0,   0.0,   'RECUENTO DIFERENCIAL'],
                ['ERITROBLASTOS',    '%',        0.0,   0.0,   'RECUENTO DIFERENCIAL'],
//                ['TOTAL',            '%',        null,  null,  'RECUENTO DIFERENCIAL'],
            ];

            $orden = 1;
            foreach ($hemogramaIzq as [$nombre, $unidad, $min, $max, $seccion]) {
                $rango = $crearRango($areaHemato, $nombre, $unidad, $min, $max);
                $crearItem($hemograma, $rango, 'IZQ', $orden++, $seccion);
            }

            // --- COLUMNA DERECHA: índices, grupo sanguíneo, coagulog., etc.
            $hemogramaDer = [
                // ÍNDICES HEMATIMÉTRICOS
                ['V.C.M.',        'fL',       73.0,  87.0,  'INDICES HEMATIMÉTRICOS'],
                ['Hb.C.M.',        'pg',       27.0,  31.0,  'INDICES HEMATIMÉTRICOS'],
                ['CHCM',          'g/L',      320.0, 360.0, 'INDICES HEMATIMÉTRICOS'],

                // GRUPO SANGUÍNEO (valores texto; rangos vacíos)
                ['Grupo sanguíneo', null,     null,  null,  'GRUPO SANGUÍNEO'],
//                ['Factor RH',        null,     null,  null,  'GRUPO SANGUÍNEO'],

                // COAGULOGRAMA
                ['Tiempo de Protrombina',      'seg',  12.0,  12.0,  'COAGULOGRAMA'],
                ['Actividad de Protrombina',   '%',    100.0, 100.0, 'COAGULOGRAMA'],
                ['INR',                        '',     1.0,   1.0,   'COAGULOGRAMA'],
                ['ATTP',                       'seg',  20.0,  39.0,  'COAGULOGRAMA'],
                ['VES',                        'mm/h', 0.0,   20.0,  'COAGULOGRAMA'],

                // Otros
//                ['Reticulocitos',   '%',       0.5,   2.5,   'RETICULOCITOS'],
                ['IPR',             '',        0.5,   2.5,   'RETICULOCITOS'],
                ['IPR2',             '',        0.5,   1.5,   'RETICULOCITOS'],
                ['Fibrinógeno',     'g/L',     2.0,   4.0,   'RETICULOCITOS'],
            ];

            $orden = 1;
            foreach ($hemogramaDer as [$nombre, $unidad, $min, $max, $seccion]) {
                $rango = $crearRango($areaHemato, $nombre, $unidad, $min, $max);
                $crearItem($hemograma, $rango, 'DER', $orden++, $seccion);
            }

            /* =========================================================
             * 3) QUÍMICA SANGUÍNEA + SEROLOGÍA  (Área 2)
             * ======================================================= */

            // --- COLUMNA IZQUIERDA (QUÍMICA SANGUÍNEA clásica)
            $quimicaIzq = [
                // Parte superior (área química)
                ['Ácido úrico',          'mg/dL', 2.6,   7.2,   'QUÍMICA SANGUÍNEA'],
                ['Albumina',             'g/dL',  3.4,   4.8,   'QUÍMICA SANGUÍNEA'],
                ['Proteínas totales',    'g/dL',  6.0,   7.8,   'QUÍMICA SANGUÍNEA'],
                ['Bilirrubina Total',    'mg/dL', null,  1.0,   'QUÍMICA SANGUÍNEA'],
                ['Bilirrubina Directa',  'mg/dL', null,  0.2,   'QUÍMICA SANGUÍNEA'],
                ['Bilirrubina Indirecta','mg/dL', null,  0.8,   'QUÍMICA SANGUÍNEA'],
                ['G.O.T. (AST)',         'U/L',   null,  50.0,  'QUÍMICA SANGUÍNEA'],
                ['G.P.T. (ALT)',         'U/L',   null,  41.0,  'QUÍMICA SANGUÍNEA'],
                ['Fosfatasa alcalina',   'U/L',   null,  115.0, 'QUÍMICA SANGUÍNEA'],
                ['GGT',                  'U/L',   9.0,   61.0,  'QUÍMICA SANGUÍNEA'],
                ['Amilasa',              'U/dL',  null,  90.0,  'QUÍMICA SANGUÍNEA'],
                ['Glucosa',              'mg/dL', 70.0,  105.0, 'QUÍMICA SANGUÍNEA'],
                ['Urea',                 'mg/dL', 15.0,  45.0,  'QUÍMICA SANGUÍNEA'],
                ['NUS',                  'mg/dL', null,  null,  'QUÍMICA SANGUÍNEA'],
                ['Creatinina',           'mg/dL', 0.5,   1.4,   'QUÍMICA SANGUÍNEA'],

                // Perfil lipídico y enzimas
                ['Triglicéridos',        'mg/dL', null,  150.0, 'QUÍMICA SANGUÍNEA'],
                ['Colesterol',           'mg/dL', null,  200.0, 'QUÍMICA SANGUÍNEA'],
                ['HDL Colesterol',       'mg/dL', 35.0,  null,  'QUÍMICA SANGUÍNEA'],
                ['LDL Colesterol',       'mg/dL', null,  129.0, 'QUÍMICA SANGUÍNEA'],
                ['VLDL Colesterol',      'mg/dL', null,  null,  'QUÍMICA SANGUÍNEA'],
                ['CK-total',             'U/L',   25.0,  192.0, 'QUÍMICA SANGUÍNEA'],
                ['CK-MB',                'U/L',   0.0,   24.0,  'QUÍMICA SANGUÍNEA'],
                ['Hierro sérico',        'µg/dL', 50.0,  170.0, 'QUÍMICA SANGUÍNEA'],
                ['LDH',                  'U/L',   200.0, 730.0, 'QUÍMICA SANGUÍNEA'],
            ];

            $orden = 1;
            foreach ($quimicaIzq as [$nombre, $unidad, $min, $max, $seccion]) {
                $rango = $crearRango($areaQuimica, $nombre, $unidad, $min, $max);
                $crearItem($quimica, $rango, 'IZQ', $orden++, $seccion);
            }

            // --- COLUMNA DERECHA: Hb glicosilada + electrolitos + serología
            $quimicaDer = [
                // QUÍMICA SANGUÍNEA
                ['HB glicosilada',       '%',     null,  null,  'QUÍMICA SANGUÍNEA'],
                ['HB A1C',               '%',     null,  null,  'QUÍMICA SANGUÍNEA'],
                ['Sodio',                'mmol/L',135.0, 145.0, 'QUÍMICA SANGUÍNEA'],
                ['Potasio',              'mmol/L',3.5,   5.0,   'QUÍMICA SANGUÍNEA'],
                ['Cloro',                'mmol/L',98.0,  107.0, 'QUÍMICA SANGUÍNEA'],
                ['Calcio',               'mg/dL', 8.2,   10.3,  'QUÍMICA SANGUÍNEA'],
                ['Fósforo',              'mg/dL', 2.8,   4.6,   'QUÍMICA SANGUÍNEA'],
                ['Magnesio',             'mg/dL', 1.7,   2.4,   'QUÍMICA SANGUÍNEA'],
                ['Creatinuria 24 hrs.',  'mg/24h',null,  null,  'QUÍMICA SANGUÍNEA'],
                ['Proteinuria de 24 hrs.','mg/24h',null,  null, 'QUÍMICA SANGUÍNEA'],
                ['D.C.E.',               'mg/dL', null,  null,  'QUÍMICA SANGUÍNEA'],
                ['VOLUMEN',              'mL/24h',null,  null,  'QUÍMICA SANGUÍNEA'],

                // SEROLOGÍA
                ['ASO',                  'UI/mL', null,  null,  'SEROLOGIA'],
                ['FR',                   'UI/mL', null,  null,  'SEROLOGIA'],
                ['PCR',                  'mg/L',  null,  null,  'SEROLOGIA'],
                ['Prueba rápida de VIH', null,    null,  null,  'SEROLOGIA'],
                ['RPR',                  null,    null,  null,  'SEROLOGIA'],
                ['Reacción de Widal',    null,    null,  null,  'SEROLOGIA'],
            ];

            $orden = 1;
            foreach ($quimicaDer as [$nombre, $unidad, $min, $max, $seccion]) {
                $rango = $crearRango($areaQuimica, $nombre, $unidad, $min, $max);
                $crearItem($quimica, $rango, 'DER', $orden++, $seccion);
            }
        });
    }
}
