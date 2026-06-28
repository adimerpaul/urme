<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AreaRango;

class AreaRangoUroanalisisSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $areaId = 3; // UROANÁLISIS / PARASITOLOGÍA

        $rangos = [

            // ==========================
            // EXAMEN FÍSICO
            // ==========================
            ['area_id' => $areaId, 'rango_nombre' => 'Cantidad',   'rango_minimo' => null, 'rango_maximo' => null, 'unidad' => 'ml',      'interpretacion' => null],
            ['area_id' => $areaId, 'rango_nombre' => 'Color',      'rango_minimo' => null, 'rango_maximo' => null, 'unidad' => null,      'interpretacion' => 'Amarillo / Sui-generis', 'lista' => 'Amarillo,Rojizo,Amarillo pajizo,Ámbar,Pardo Caoba'],
//            Olor : Sui-Generis, Fétido, Inoloro
            ['area_id' => $areaId, 'rango_nombre' => 'Olor',       'rango_minimo' => null, 'rango_maximo' => null, 'unidad' => null,      'interpretacion' => 'Sui-generis', 'lista' => 'Sui-generis,Fétido,Inoloro'],
//            ASPECTO: Límpido, Turbio, Opalescente, Lig. Opalescente
            ['area_id' => $areaId, 'rango_nombre' => 'Aspecto',    'rango_minimo' => null, 'rango_maximo' => null, 'unidad' => null,      'interpretacion' => 'Límpido', 'lista' => 'Límpido,Turbio,Opalescente,Lig. Opalescente'],
//            REACCION
//pH 	5.0 ácido
//pH 	6.0 ácido
//pH 	6.5 ácido
//pH 	7.0 neutro
//pH 	7.5 alcalino
//pH 	8.0 alcalino
//pH 	9.0 alcalino
            ['area_id' => $areaId, 'rango_nombre' => 'Reacción',   'rango_minimo' => null, 'rango_maximo' => null, 'unidad' => null,      'interpretacion' => 'PH 6.0 ácido', 'lista' => 'PH 5.0 ácido,PH 6.0 ácido,PH 6.5 ácido,PH 7.0 neutro,PH 7.5 alcalino,PH 8.0 alcalino,PH 9.0 alcalino'],
//            DENSIDAD:
//•	1.000
//•	1.005
//•	1.010
//•	1.015
//•	1.020
//•	1.025
//ESPUMA:
//•	Fugaz
//•	Blanco Fugaz
//•	Persistente
//SEDIMENTO:
//•	Muy Escaso
//•	Escaso
//•	Moderado
//•	Abundante

    ['area_id' => $areaId, 'rango_nombre' => 'Densidad',   'rango_minimo' => null, 'rango_maximo' => null, 'unidad' => 'mmHg',    'interpretacion' => '1.020', 'lista' => '1.000,1.005,1.010,1.015,1.020,1.025'],
            ['area_id' => $areaId, 'rango_nombre' => 'Espuma',     'rango_minimo' => null, 'rango_maximo' => null, 'unidad' => null,      'interpretacion' => 'Blanco fugaz', 'lista' => 'Fugaz,Blanco Fugaz,Persistente'],
            ['area_id' => $areaId, 'rango_nombre' => 'Sedimento',  'rango_minimo' => null, 'rango_maximo' => null, 'unidad' => null,      'interpretacion' => 'Escaso' , 'lista' => 'Muy Escaso,Escaso,Moderado,Abundante'],

            // ==========================
            // EXAMEN QUÍMICO
            // ==========================
//            PROTEINAS	GLUCOSA	SANGRE	CETONAS
//NO CONTIENE	NO CONTIENE	NO CONTIENE	NO CONTIENE
//TRAZAS	TRAZAS	TRAZAS	TRAZAS (5mg/dl)
//CONTIENE + (30mg/dl)	CONTIENE + (250mg/dl)	CONTIENE + (50 cel./ul)	CONTIENE+ (15mg/dl)
//CONTIENE ++ (100 mg/dl)	CONTIENE ++ (500 mg/dl)	CONTIENE ++ (80 cel./ul)	CONTIENE++ (40mg/dl)
//CONTIENE +++ (300mg/dl)	CONTIENE +++ (1000mg/dl)	CONTIENE +++ (200 cel./ul)	CONTIENE+++ (80mg/dl)
//CONTIENE ++++ (mg/dl)	CONTIENE ++++ (>=2000 mg/dl)	 	CONTIENE++++ (160mg/dl)
//
//BILIRRUBINAS	urobilinogeno	NITRITOS
//NO CONTIENE	NORMAL (0.2 mg/dL)	NEGATIVO
//CONTIENE+(1mg/dl)	1 mg/dL	POSITIVO
//CONTIENE++(2mg/dl)	2 mg/dl
//CONTIENE+++(4mg/dl)	4 mg/dl


    ['area_id' => $areaId, 'rango_nombre' => 'Proteínas',      'rango_minimo' => null, 'rango_maximo' => null, 'unidad' => null, 'interpretacion' => 'NO CONTIENE', 'lista' => 'NO CONTIENE,TRAZAS,CONTIENE + (30mg/dl),CONTIENE ++ (100 mg/dl),CONTIENE +++ (300mg/dl),CONTIENE ++++ (mg/dl)'],
            ['area_id' => $areaId, 'rango_nombre' => 'Glucosa',        'rango_minimo' => null, 'rango_maximo' => null, 'unidad' => null, 'interpretacion' => 'NO CONTIENE', 'lista' => 'NO CONTIENE,TRAZAS,CONTIENE + (250mg/dl),CONTIENE ++ (500 mg/dl),CONTIENE +++ (1000mg/dl),CONTIENE ++++ (>=2000 mg/dl)'],
            ['area_id' => $areaId, 'rango_nombre' => 'Sangre',         'rango_minimo' => null, 'rango_maximo' => null, 'unidad' => null, 'interpretacion' => 'NO CONTIENE', 'lista' => 'NO CONTIENE,TRAZAS,CONTIENE + (50 cel./ul),CONTIENE ++ (80 cel./ul),CONTIENE +++ (200 cel./ul)'],
            ['area_id' => $areaId, 'rango_nombre' => 'Cetonas',        'rango_minimo' => null, 'rango_maximo' => null, 'unidad' => null, 'interpretacion' => 'NO CONTIENE', 'lista' => 'NO CONTIENE,TRAZAS (5mg/dl),CONTIENE+ (15mg/dl),CONTIENE++ (40mg/dl),CONTIENE+++ (80mg/dl),CONTIENE++++ (160mg/dl)'],
            ['area_id' => $areaId, 'rango_nombre' => 'Bilirrubina',    'rango_minimo' => null, 'rango_maximo' => null, 'unidad' => null, 'interpretacion' => 'NO CONTIENE', 'lista' => 'NO CONTIENE,CONTIENE+(1mg/dl),CONTIENE++(2mg/dl),CONTIENE+++(4mg/dl)'],
            ['area_id' => $areaId, 'rango_nombre' => 'Urobilinógeno',  'rango_minimo' => null, 'rango_maximo' => null, 'unidad' => null, 'interpretacion' => 'NORMAL',      'lista' => 'NORMAL (0.2 mg/dL),1 mg/dL,2 mg/dl,4 mg/dl'],
            ['area_id' => $areaId, 'rango_nombre' => 'Nitritos',       'rango_minimo' => null, 'rango_maximo' => null, 'unidad' => null, 'interpretacion' => 'NEGATIVO',   'lista' => 'NEGATIVO,POSITIVO'],

            // ==========================
            // EXAMEN MICROSCÓPICO
            // ==========================
            ['area_id' => $areaId, 'rango_nombre' => 'Células epiteliales', 'rango_minimo' => 0, 'rango_maximo' => 1, 'unidad' => 'xcampo/uL', 'interpretacion' => '0 - 1'],
            ['area_id' => $areaId, 'rango_nombre' => 'Leucocitos',          'rango_minimo' => 0, 'rango_maximo' => 1, 'unidad' => 'xcampo/uL', 'interpretacion' => '0 - 1'],
            ['area_id' => $areaId, 'rango_nombre' => 'Hematies',            'rango_minimo' => 0, 'rango_maximo' => 1, 'unidad' => 'xcampo/uL', 'interpretacion' => '0 - 1'],
            ['area_id' => $areaId, 'rango_nombre' => 'Bacterias',           'rango_minimo' => null, 'rango_maximo' => null, 'unidad' => 'xcampo/uL', 'interpretacion' => 'Escaso'],
            ['area_id' => $areaId, 'rango_nombre' => 'Filamento mucoide',   'rango_minimo' => null, 'rango_maximo' => null, 'unidad' => 'xcampo/uL', 'interpretacion' => null],
            ['area_id' => $areaId, 'rango_nombre' => 'Cilindros',           'rango_minimo' => null, 'rango_maximo' => null, 'unidad' => 'xcampo/uL', 'interpretacion' => null],
            ['area_id' => $areaId, 'rango_nombre' => 'Células',             'rango_minimo' => null, 'rango_maximo' => null, 'unidad' => 'xcampo/uL', 'interpretacion' => null],
            ['area_id' => $areaId, 'rango_nombre' => 'Cristales',           'rango_minimo' => null, 'rango_maximo' => null, 'unidad' => null,        'interpretacion' => 'Fosfato amorfo'],

            // ==========================
            // OTROS EXÁMENES
            // ==========================
//            NORMAL
//DISMORFICO
//ISOMORFICO
//ESTRELLADO (CRENADOS)
//FANTASMA
//SEPTADOS
//POLIDIVERTICULADOS
//ESPICULADOS
//ANULARES
//MONODIVERTICULARES
//MIXTOS
//VACIOS
            ['area_id' => $areaId, 'rango_nombre' => 'Morfología eritrocitaria', 'rango_minimo' => null, 'rango_maximo' => null, 'unidad' => 'xcampo/uL', 'interpretacion' => 'Normal', 'lista' => 'NORMAL,DISMORFICO,ISOMORFICO,ESTRELLADO (CRENADOS),FANTASMA,SEPTADOS,POLIDIVERTICULADOS,ESPICULADOS,ANULARES,MONODIVERTICULARES,MIXTOS,VACIOS'],
        ];

        foreach ($rangos as $rango) {
            AreaRango::create($rango);
        }
    }
}
