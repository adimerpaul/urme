<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Servicio;
use Illuminate\Database\Seeder;

class ServiciosSeeder extends Seeder
{
    public function run(): void
    {
        $areas = [
            // ---------------------------------------------------------
            // HEMATOLOGÍA
            // ---------------------------------------------------------
            [
                'name' => 'HEMATOLOGÍA (Area 2)',
                'title' => 'HEMATOLOGÍA',
                'servicios' => [
                    [ 'codigo' => 1,  'nombre' => 'COAGULOGRAMA (TP,RECUENTO DE PLAQUETAS, APTT)', 'metodo' => 'M/SA', 'precio' => 55 ],
                    [ 'codigo' => 2,  'nombre' => 'ERITROSEDIMENTACIÓN (VSG- VES)',                  'metodo' => 'M',    'precio' => 15 ],
                    [ 'codigo' => 3,  'nombre' => 'FIBRINÓGENO',                                    'metodo' => 'M',    'precio' => 30 ],
                    [ 'codigo' => 4,  'nombre' => 'FROTIS SANGUÍNEO/LEUCOGRAMA',                    'metodo' => 'M',    'precio' => 15 ],
                    [ 'codigo' => 5,  'nombre' => 'GRUPO SANGUÍNEO Y FACTOR',                       'metodo' => 'M',    'precio' => 15 ],
                    [ 'codigo' => 6,  'nombre' => 'HEMOGRAMA COMPLETO+ PLAQUETAS',                  'metodo' => 'A',    'precio' => 30 ],
                    [ 'codigo' => 7,  'nombre' => 'HEMATOCRITO Y HEMOGLOBINA',                      'metodo' => 'M',    'precio' => 15 ],
                    [ 'codigo' => 8,  'nombre' => 'ÍNDICES HEMATIMÉTRICOS',                         'metodo' => 'A',    'precio' => 20 ],
                    [ 'codigo' => 9,  'nombre' => 'MORFOLOGÍA DE GLÓBULOS ROJOS',                   'metodo' => 'M',    'precio' => 15 ],
                    [ 'codigo' => 10, 'nombre' => 'RECUENTO DE PLAQUETAS',                          'metodo' => 'M/A',  'precio' => 15 ],
                    [ 'codigo' => 11, 'nombre' => 'RECUENTO DE RETICULOCITOS',                      'metodo' => 'M',    'precio' => 15 ],
                    [ 'codigo' => 12, 'nombre' => 'TIEMPO DE PROTROMBINA (TP)',                     'metodo' => 'M/SA', 'precio' => 20 ],
                    [ 'codigo' => 13, 'nombre' => 'TIEMPO PARCIAL DE TROMBOPLASTINA ACTIVADA (APTT)','metodo' => 'M/SA','precio' => 20 ],
                ],
            ],

            // ---------------------------------------------------------
            // QUÍMICA SANGUÍNEA
            // ---------------------------------------------------------
            [
                'name' => 'QUÍMICA SANGUÍNEA Y SEROLOGÍA (Area 3)',
                'title' => 'QUÍMICA SANGUÍNEA Y SEROLOGÍA',
                'servicios' => [
                    [ 'codigo' => 14, 'nombre' => 'ÁCIDO ÚRICO',                                      'metodo' => 'M/A', 'precio' => 20, 'subarea'=>'Química Sanguínea' ],
                    [ 'codigo' => 15, 'nombre' => 'ALBUMINA',                                        'metodo' => 'M/A', 'precio' => 20, 'subarea'=>'Química Sanguínea' ],
                    [ 'codigo' => 16, 'nombre' => 'AMILASA',                                         'metodo' => 'M/A', 'precio' => 20, 'subarea'=>'Química Sanguínea' ],
                    [ 'codigo' => 17, 'nombre' => 'BILIRRUBINAS TOTALES Y FRACCIONADAS',             'metodo' => 'M/A', 'precio' => 20, 'subarea'=>'Química Sanguínea' ],
                    [ 'codigo' => 18, 'nombre' => 'CALCIO',                                          'metodo' => 'M',   'precio' => 30, 'subarea'=>'Química Sanguínea' ],
                    [ 'codigo' => 19, 'nombre' => 'CITOQUÍMICO LÍQUIDO CEFALORRAQUÍDEO Y OTROS LÍQUIDOS','metodo' => 'M/A','precio' => 60, 'subarea'=>'Química Sanguínea' ],
                    [ 'codigo' => 20, 'nombre' => 'CK TOTAL',                                        'metodo' => 'M',   'precio' => 30, 'subarea'=>'Química Sanguínea' ],
                    [ 'codigo' => 21, 'nombre' => 'CK MB',                                           'metodo' => 'M',   'precio' => 30, 'subarea'=>'Química Sanguínea' ],
                    [ 'codigo' => 22, 'nombre' => 'CLEARENCE DE CREATININA',                         'metodo' => 'M',   'precio' => 35, 'subarea'=>'Química Sanguínea' ],
                    [ 'codigo' => 23, 'nombre' => 'COLESTEROL',                                      'metodo' => 'M/A', 'precio' => 20, 'subarea'=>'Química Sanguínea' ],
                    [ 'codigo' => 24, 'nombre' => 'CREATININA EN ORINA (CREATINURIA)',               'metodo' => 'M/A', 'precio' => 20, 'subarea'=>'Química Sanguínea' ],
                    [ 'codigo' => 25, 'nombre' => 'CREATININA SÉRICA',                               'metodo' => 'M/A', 'precio' => 20, 'subarea'=>'Química Sanguínea' ],
                    [ 'codigo' => 26, 'nombre' => 'ELECTROLITOS (SODIO, POTASIO, CLORO) (NA,K,CL)',  'metodo' => 'M/A', 'precio' => 90, 'subarea'=>'Química Sanguínea' ],
                    [ 'codigo' => 27, 'nombre' => 'FOSFATASA ALCALINA',                              'metodo' => 'M/A', 'precio' => 20, 'subarea'=>'Química Sanguínea' ],
                    [ 'codigo' => 28, 'nombre' => 'FÓSFORO',                                         'metodo' => 'M/A', 'precio' => 20, 'subarea'=>'Química Sanguínea' ],
                    [ 'codigo' => 29, 'nombre' => 'GAMA GLUTAMIL TRANSFERASA (GGT)',                 'metodo' => 'M/A', 'precio' => 30, 'subarea'=>'Química Sanguínea' ],
                    [ 'codigo' => 30, 'nombre' => 'GASOMETRÍA ARTERIAL O VENOSA',                    'metodo' => 'A',   'precio' => 200, 'subarea'=>'Química Sanguínea' ],
                    [ 'codigo' => 31, 'nombre' => 'GLICEMIA',                                        'metodo' => 'M/A', 'precio' => 20, 'subarea'=>'Química Sanguínea' ],
                    [ 'codigo' => 32, 'nombre' => 'HDLc, LDLc, VLDLc',                               'metodo' => 'M',   'precio' => 20, 'subarea'=>'Química Sanguínea' ],
                    [ 'codigo' => 33, 'nombre' => 'HEMOGLOBINA GLICOSILADA A1c',                     'metodo' => 'SA',  'precio' => 90, 'subarea'=>'Química Sanguínea' ],
                    [ 'codigo' => 34, 'nombre' => 'HIERRO',                                          'metodo' => 'M',   'precio' => 30, 'subarea'=>'Química Sanguínea' ],
                    [ 'codigo' => 35, 'nombre' => 'IONOGRAMA (NA,K,CL,CA,Mg,P)',                     'metodo' => 'M',   'precio' => 180, 'subarea'=>'Química Sanguínea' ],
                    [ 'codigo' => 36, 'nombre' => 'LACTATO DESHIDROGENASA ( LDH )',                  'metodo' => 'M',   'precio' => 20, 'subarea'=>'Química Sanguínea' ],
                    [ 'codigo' => 37, 'nombre' => 'LIPASA',                                          'metodo' => 'M',   'precio' => 40, 'subarea'=>'Química Sanguínea' ],
                    [ 'codigo' => 38, 'nombre' => 'MAGNESIO',                                        'metodo' => 'M/A', 'precio' => 30, 'subarea'=>'Química Sanguínea' ],
                    [ 'codigo' => 39, 'nombre' => 'NITROGENO UREICO SERICO (NUS)',                   'metodo' => 'M',   'precio' => 20, 'subarea'=>'Química Sanguínea' ],
                    [ 'codigo' => 40, 'nombre' => 'PROTEINAS TOTALES',                               'metodo' => 'M/A', 'precio' => 20, 'subarea'=>'Química Sanguínea' ],
                    [ 'codigo' => 41, 'nombre' => 'PROTEINOGRAMA (PROTEÍNAS TOTALES, ALBÚMINA, GLOBULINA)', 'metodo' => 'M/A','precio' => 40, 'subarea'=>'Química Sanguínea' ],
                    [ 'codigo' => 42, 'nombre' => 'PERFIL HEPÁTICO O HEPATOGRAMA (BILIRRUBINAS TOTALES Y FRACCIONADAS, FOSFATASA ALCALINA, GOT, GPT, GGT, TP)', 'metodo' => 'M/A', 'precio' => 120, 'subarea'=>'Química Sanguínea' ],
                    [ 'codigo' => 43, 'nombre' => 'PERFIL LIPÍDICO O LIPIDOGRAMA (COLESTEROL, TRIGLICERIDOS, HDLc,LDLc,VLDLc)', 'metodo' => 'M/A', 'precio' => 80, 'subarea'=>'Química Sanguínea' ],
                    [ 'codigo' => 44, 'nombre' => 'PERFIL RENAL (CREATININA SÉRICA, ÁCIDO ÚRICO, UREA)', 'metodo' => 'M/A', 'precio' => 60, 'subarea'=>'Química Sanguínea' ],
                    [ 'codigo' => 45, 'nombre' => 'PROTEINURIA 24 HRS',                               'metodo' => 'M/A', 'precio' => 20, 'subarea'=>'Química Sanguínea' ],
                    [ 'codigo' => 46, 'nombre' => 'PRUEBA DE TOLERANCIA A LA GLUCOSA (4 MEDICIONES) (PTG)', 'metodo' => 'M/A', 'precio' => 80, 'subarea'=>'Química Sanguínea' ],
                    [ 'codigo' => 47, 'nombre' => 'PRUEBA DE TOLERANCIA A LA GLUCOSA (3 MEDICIONES) (PTG)', 'metodo' => 'M/A', 'precio' => 60, 'subarea'=>'Química Sanguínea' ],
                    [ 'codigo' => 48, 'nombre' => 'TRANSAMINASAS GOT',                                'metodo' => 'M',   'precio' => 15, 'subarea'=>'Química Sanguínea' ],
                    [ 'codigo' => 49, 'nombre' => 'TRANSAMINASAS GPT',                                'metodo' => 'M',   'precio' => 15, 'subarea'=>'Química Sanguínea' ],
                    [ 'codigo' => 50, 'nombre' => 'TRIGLICÉRIDOS',                                    'metodo' => 'M/A', 'precio' => 20, 'subarea'=>'Química Sanguínea' ],
                    [ 'codigo' => 51, 'nombre' => 'UREA',                                             'metodo' => 'M/A', 'precio' => 20, 'subarea'=>'Química Sanguínea' ],
//                ],
//            ],
//
//            // ---------------------------------------------------------
//            // INMUNOSEROLOGÍA
//            // ---------------------------------------------------------
//            [
//                'name' => 'SEROLOGÍA (Area 3)',
//                'servicios' => [
                    [ 'codigo' => 52, 'nombre' => 'ASTO O ASO',                                        'metodo' => 'M', 'precio' => 30, 'subarea'=>'Serología' ],
                    [ 'codigo' => 53, 'nombre' => 'FACTOR REUMATOIDEO (FR)',                           'metodo' => 'M', 'precio' => 30, 'subarea'=>'Serología' ],
                    [ 'codigo' => 54, 'nombre' => 'PCR CUALITATIVO (PROTEÍNA C REACTIVA)',             'metodo' => 'M', 'precio' => 30, 'subarea'=>'Serología' ],
                    [ 'codigo' => 55, 'nombre' => 'PRUEBA RAPIDA PARA HEPATITIS B',                    'metodo' => 'M', 'precio' => 60, 'subarea'=>'Serología' ],
                    [ 'codigo' => 56, 'nombre' => 'PRUEBA RAPIDA PARA HEPATITIS C',                    'metodo' => 'M', 'precio' => 60, 'subarea'=>'Serología' ],
                    [ 'codigo' => 57, 'nombre' => 'PRUEBA RAPIDA PARA CHAGAS',                         'metodo' => 'M', 'precio' => 60, 'subarea'=>'Serología' ],
                    [ 'codigo' => 58, 'nombre' => 'PRUEBA RAPIDA PARA VIH',                            'metodo' => 'M', 'precio' => 40, 'subarea'=>'Serología' ],
                    [ 'codigo' => 59, 'nombre' => 'PRUEBA RAPIDA PARA SIFILIS',                        'metodo' => 'M', 'precio' => 40, 'subarea'=>'Serología' ],
                    [ 'codigo' => 60, 'nombre' => 'PRUEBA RAPIDA PARA TROPONINA',                      'metodo' => 'M', 'precio' => 40, 'subarea'=>'Serología' ],
                    [ 'codigo' => 61, 'nombre' => 'REACCIÓN DE WIDAL',                                 'metodo' => 'M', 'precio' => 30, 'subarea'=>'Serología' ],
                    [ 'codigo' => 62, 'nombre' => 'RPR- VDRL',                                         'metodo' => 'M', 'precio' => 30, 'subarea'=>'Serología' ],
                    [ 'codigo' => 63, 'nombre' => 'TEST DE EMBARAZO EN SUERO (GONADOTROFINA CORIÓNICA HUMANA CUALITATIVO)', 'metodo' => 'M', 'precio' => 25, 'subarea'=>'Serología' ],
                ],
            ],

            // ---------------------------------------------------------
            // UROANÁLISIS
            // ---------------------------------------------------------
            [
                'name' => 'UROANÁLISIS  (Area 4)',
                'title' => 'UROANÁLISIS',
                'servicios' => [
                    [ 'codigo' => 65, 'nombre' => 'EXAMEN GENERAL DE ORINA',                           'metodo' => 'M', 'precio' => 20 , 'subarea'=>'Uroanálisis' ],
                    [ 'codigo' => 66, 'nombre' => 'MORFOLOGÍA DE ERITROCITOS',                         'metodo' => 'M', 'precio' => 10 , 'subarea'=>'Uroanálisis' ],
                    [ 'codigo' => 67, 'nombre' => 'TEST DE CRISTALIZACIÓN',                            'metodo' => 'M', 'precio' => 10, 'subarea'=>'Uroanálisis' ],
                ],
            ],
//
//            // ---------------------------------------------------------
//            // PARASITOLOGÍA Y ESTUDIOS EN HECES FECALES
//            // ---------------------------------------------------------
            [
                'name' => 'PARASITOLOGÍA (Area 4)',
                'title' => 'PARASITOLOGÍA',
                'servicios' => [
                    [ 'codigo' => 68, 'nombre' => 'AMEBAS EN FRESCO',                                  'metodo' => 'MANUAL', 'precio' => 15, 'area'=>'Parasitolgía' ],
                    [ 'codigo' => 69, 'nombre' => 'BENEDICT+ pH O PRUEBA DE TOLERANCIA A LA LACTOSA EN HECES', 'metodo' => 'MANUAL', 'precio' => 20, 'area'=>'Parasitolgía' ],
                    [ 'codigo' => 70, 'nombre' => 'COPROPARASITOLÓGICO SIMPLE',                        'metodo' => 'MANUAL', 'precio' => 15, 'area'=>'Parasitolgía' ],
                    [ 'codigo' => 71, 'nombre' => 'COPROPARASITOLÓGICO SERIADO',                       'metodo' => 'MANUAL', 'precio' => 25, 'area'=>'Parasitolgía' ],
                    [ 'codigo' => 72, 'nombre' => 'EXAMEN DIRECTO PARA LEISHMANIA',                    'metodo' => 'MANUAL', 'precio' => 20, 'area'=>'Parasitolgía' ],
                    [ 'codigo' => 73, 'nombre' => 'GOTA GRUESA PARA MALARIA',                          'metodo' => 'MANUAL', 'precio' => 20, 'area'=>'Parasitolgía' ],
                    [ 'codigo' => 74, 'nombre' => 'MICROMÉTODO PARA CHAGAS',                           'metodo' => 'MANUAL', 'precio' => 15, 'area'=>'Parasitolgía' ],
                    [ 'codigo' => 75, 'nombre' => 'MOCO FECAL',                                        'metodo' => 'MANUAL', 'precio' => 15, 'area'=>'Parasitolgía' ],
                    [ 'codigo' => 76, 'nombre' => 'PRUEBA RAPIDA PARA ROTAVIRUS',                      'metodo' => 'MANUAL', 'precio' => 35, 'area'=>'Parasitolgía' ],
                    [ 'codigo' => 77, 'nombre' => 'SANGRE OCULTA EN SANGRE',                           'metodo' => 'MANUAL', 'precio' => 15, 'area'=>'Parasitolgía' ],
                    [ 'codigo' => 78, 'nombre' => 'TÉCNICA DE GRAHAM',                                 'metodo' => 'MANUAL', 'precio' => 15, 'area'=>'Parasitolgía' ],
                ],
            ],

            // ---------------------------------------------------------
            // BACTERIOLOGÍA
            // ---------------------------------------------------------
            [
                'name' => 'MICROBIOLOGÍA (Area 5)',
                'title' => 'MICROBIOLOGÍA',
                'servicios' => [
                    [ 'codigo' => 79, 'nombre' => 'CULTIVO Y ANTIBIOGRAMA PARA GÉRMENES COMUNES',      'metodo' => 'MANUAL', 'precio' => 60 ],
                    [ 'codigo' => 80, 'nombre' => 'CULTIVO MICOLÓGICO Y FUNGIGRAMA',                   'metodo' => 'MANUAL', 'precio' => 120 ],
                    [ 'codigo' => 81, 'nombre' => 'EXAMEN EN FRESCO',                                  'metodo' => 'MANUAL', 'precio' => 15 ],
                    [ 'codigo' => 82, 'nombre' => 'FROTIS TINCIÓN GRAM',                               'metodo' => 'MANUAL', 'precio' => 15 ],
                    [ 'codigo' => 83, 'nombre' => 'HEMOCULTIVO SIMPLE (UNA TOMA)',                     'metodo' => 'MANUAL', 'precio' => 200 ],
                    [ 'codigo' => 84, 'nombre' => 'HEMOCULTIVO SERIADO (3 TOMAS)',                     'metodo' => 'MANUAL', 'precio' => 700 ],
                    [ 'codigo' => 85, 'nombre' => 'RETROCULTIVO',                                      'metodo' => 'MANUAL', 'precio' => 70 ],
                ],
            ],

            // ---------------------------------------------------------
            // INMUNOLOGÍA / INFECCIOSOS
            // ---------------------------------------------------------
            [
                'name' => 'INMUNOLOGÍA (Area 6)',
                'title' => 'INMUNOLOGÍA',
                'servicios' => [
                    [ 'codigo' => 86, 'nombre' => 'CHAGAS IgG (EN SUERO)',                             'metodo' => 'ELISA',        'precio' => 70 , 'subarea'=>'Infecciosos' ],
                    [ 'codigo' => 87, 'nombre' => 'CHAGAS (EN SUERO)',                                 'metodo' => 'HAI Y ELISA',  'precio' => 110 , 'subarea'=>'Infecciosos' ],
                    [ 'codigo' => 88, 'nombre' => 'CHAGAS IgM (EN SUERO)',                             'metodo' => 'HAI',          'precio' => 40 , 'subarea'=>'Infecciosos' ],
                    [ 'codigo' => 89, 'nombre' => 'CHLAMYDIA (IgG e IgM) (EN SUERO)',                  'metodo' => 'ELISA',        'precio' => 170 , 'subarea'=>'Infecciosos' ],
                    [ 'codigo' => 90, 'nombre' => 'CISTICERCOCIS (IgG) (EN SUERO)',                    'metodo' => 'ELISA',        'precio' => 170 , 'subarea'=>'Infecciosos' ],
                    [ 'codigo' => 91, 'nombre' => 'CITOMEGALOVIRUS (IgG e IgM) (EN SUERO)',            'metodo' => 'ELISA',        'precio' => 170 , 'subarea'=>'Infecciosos' ],
                    [ 'codigo' => 92, 'nombre' => 'EPSTEIN BARR (IgG e IgM) (EN SUERO) (MONONUCLEOSIS)','metodo' => 'ELISA',       'precio' => 220 , 'subarea'=>'Infecciosos' ],
                    [ 'codigo' => 93, 'nombre' => 'HELICOBACTER PYLORI (IgG e IgM) (EN SUERO)',        'metodo' => 'ELISA',        'precio' => 120 , 'subarea'=>'Infecciosos' ],
                    [ 'codigo' => 94, 'nombre' => 'HELICOBACTER PYLORI Ag (EN HECES)',                 'metodo' => 'ELISA',        'precio' => 120 , 'subarea'=>'Infecciosos' ],
                    [ 'codigo' => 95, 'nombre' => 'HEPATITIS A (IgM) (EN SUERO)',                      'metodo' => 'ELISA',        'precio' => 60 , 'subarea'=>'Infecciosos' ],
                    [ 'codigo' => 96, 'nombre' => 'HEPATITIS B (HBsAg) (EN SUERO)',                    'metodo' => 'ELISA',        'precio' => 60 , 'subarea'=>'Infecciosos' ],
                    [ 'codigo' => 97, 'nombre' => 'HEPATITIS B anti-CORE (EN SUERO)',                  'metodo' => 'ELISA',        'precio' => 60 , 'subarea'=>'Infecciosos' ],
                    [ 'codigo' => 98, 'nombre' => 'HEPATITIS C (IgG) (EN SUERO)',                      'metodo' => 'ELISA',        'precio' => 60 , 'subarea'=>'Infecciosos' ],
                    [ 'codigo' => 99, 'nombre' => 'HERPES (IgM) (EN SUERO)',                           'metodo' => 'ELISA',        'precio' => 120 , 'subarea'=>'Infecciosos' ],
                    [ 'codigo' => 100,'nombre' => 'HERPES (IgG) (EN SUERO)',                           'metodo' => 'ELISA',        'precio' => 120 , 'subarea'=>'Infecciosos' ],
                    [ 'codigo' => 101,'nombre' => 'HIDATIDOSIS (IgG) (EN SUERO) (EQUINOCOCCUS)',       'metodo' => 'ELISA',        'precio' => 100 , 'subarea'=>'Infecciosos' ],
                    [ 'codigo' => 102,'nombre' => 'PROCALCITONINA (EN SUERO) (BIOMARCADOR DE SEPSIS)', 'metodo' => 'ELISA',        'precio' => 120 , 'subarea'=>'Infecciosos' ],
                    [ 'codigo' => 103,'nombre' => 'RUBEOLA (IgM) (EN SUERO)',                          'metodo' => 'ELISA',        'precio' => 90 , 'subarea'=>'Infecciosos' ],
                    [ 'codigo' => 104,'nombre' => 'TOXOPLASMOSIS (IgM e IgG) (EN SUERO)',              'metodo' => 'ELISA',        'precio' => 120 , 'subarea'=>'Infecciosos' ],
                    [ 'codigo' => 105,'nombre' => 'TORCH (TOXO (IgM e IgG), CHAGAS (HAI Y ELISA), RUBEOLA (IgM), CITOMEGALOVIRUS (IgG e IgM))', 'metodo' => 'ELISA Y HAI', 'precio' => 410 , 'subarea'=>'Infecciosos' ],

                    [ 'codigo' => 106, 'nombre' => 'ACTH (PLASMA CON EDTA)',                           'metodo' => 'ELISA Y CLIA', 'precio' => 200, 'subarea'=>'Hormonas' ],
                    [ 'codigo' => 107, 'nombre' => 'CORTISOL (2 TOMAS 8:00 Y 16:00)',                  'metodo' => 'ELISA Y CLIA', 'precio' => 120, 'subarea'=>'Hormonas' ],
                    [ 'codigo' => 108, 'nombre' => 'ESTRADIOL (ESTRÓGENOS)',                           'metodo' => 'ELISA Y CLIA', 'precio' => 100, 'subarea'=>'Hormonas' ],
                    [ 'codigo' => 109, 'nombre' => 'FSH (HORMONA FOLÍCULO ESTIMULANTE)',               'metodo' => 'ELISA Y CLIA', 'precio' => 100, 'subarea'=>'Hormonas' ],
                    [ 'codigo' => 110, 'nombre' => 'HGC (CUANTIFICACIÓN HORMONA GONADOTROFINA CORIÓNICA FRACCIÓN BETA)', 'metodo' => 'ELISA Y CLIA', 'precio' => 60, 'subarea'=>'Hormonas' ],
                    [ 'codigo' => 111, 'nombre' => 'HGH - HORMONA DEL CRECIMIENTO (1 Toma sin estimulación)', 'metodo' => 'ELISA Y CLIA', 'precio' => 100, 'subarea'=>'Hormonas' ],
                    [ 'codigo' => 112, 'nombre' => 'LH (HORMONA LUTENIZANTE)',                         'metodo' => 'ELISA Y CLIA', 'precio' => 100, 'subarea'=>'Hormonas' ],
                    [ 'codigo' => 113, 'nombre' => 'PROGESTERONA',                                     'metodo' => 'ELISA Y CLIA', 'precio' => 100, 'subarea'=>'Hormonas' ],
                    [ 'codigo' => 114, 'nombre' => 'PROLACTINA',                                       'metodo' => 'ELISA Y CLIA', 'precio' => 100, 'subarea'=>'Hormonas' ],
                    [ 'codigo' => 115, 'nombre' => 'PARATHORMONA',                                     'metodo' => 'ELISA Y CLIA', 'precio' => 100, 'subarea'=>'Hormonas' ],
                    [ 'codigo' => 116, 'nombre' => 'TSH',                                              'metodo' => 'ELISA Y CLIA', 'precio' => 100, 'subarea'=>'Hormonas' ],
                    [ 'codigo' => 117, 'nombre' => 'TSH NEONATAL (TAMIZAJE)',                          'metodo' => 'ELISA Y CLIA', 'precio' => 90, 'subarea'=>'Hormonas' ],
                    [ 'codigo' => 118, 'nombre' => 'T 3 TOTAL',                                        'metodo' => 'ELISA Y CLIA', 'precio' => 60, 'subarea'=>'Hormonas' ],
                    [ 'codigo' => 119, 'nombre' => 'T 4 TOTAL',                                        'metodo' => 'ELISA Y CLIA', 'precio' => 60, 'subarea'=>'Hormonas' ],
                    [ 'codigo' => 120, 'nombre' => 'T 4 LIBRE',                                        'metodo' => 'ELISA Y CLIA', 'precio' => 60, 'subarea'=>'Hormonas' ],
                    [ 'codigo' => 121, 'nombre' => 'TESTOSTERONA TOTAL',                               'metodo' => 'ELISA Y CLIA', 'precio' => 100, 'subarea'=>'Hormonas' ],
                    [ 'codigo' => 122, 'nombre' => 'TESTOSTERONA LIBRE',                               'metodo' => 'ELISA Y CLIA', 'precio' => 100, 'subarea'=>'Hormonas' ],
                    [ 'codigo' => 123, 'nombre' => 'TIROGLOBULINA',                                    'metodo' => 'ELISA Y CLIA', 'precio' => 90, 'subarea'=>'Hormonas' ],

                    [ 'codigo' => 124, 'nombre' => 'ANA (ANTICUERPOS ANTINUCLEARES)',                  'metodo' => 'ELISA', 'precio' => 90, 'subarea'=>'Marcadores de Autoinmunidad' ],
                    [ 'codigo' => 125, 'nombre' => 'ANTI- DNA',                                        'metodo' => 'ELISA', 'precio' => 90, 'subarea'=>'Marcadores de Autoinmunidad' ],
                    [ 'codigo' => 126, 'nombre' => 'ANTI-TPO (ANTI- PEROXIDASA)',                      'metodo' => 'ELISA Y CLIA', 'precio' => 90, 'subarea'=>'Marcadores de Autoinmunidad' ],
                    [ 'codigo' => 127, 'nombre' => 'ANCA -P',                                          'metodo' => 'ELISA', 'precio' => 90, 'subarea'=>'Marcadores de Autoinmunidad' ],
                    [ 'codigo' => 128, 'nombre' => 'ANCA -C',                                          'metodo' => 'ELISA', 'precio' => 90, 'subarea'=>'Marcadores de Autoinmunidad' ],
                    [ 'codigo' => 129, 'nombre' => 'ANTI-TG (ANTI-TIROGLOBULINA)',                     'metodo' => 'ELISA Y CLIA', 'precio' => 60, 'subarea'=>'Marcadores de Autoinmunidad' ],
                    [ 'codigo' => 130, 'nombre' => 'ANTI-CCP',                                         'metodo' => 'ELISA', 'precio' => 90, 'subarea'=>'Marcadores de Autoinmunidad' ],
                    [ 'codigo' => 131, 'nombre' => 'PERIL ENA',                                        'metodo' => 'ELISA', 'precio' => 200, 'subarea'=>'Marcadores de Autoinmunidad' ],

                    [ 'codigo' => 132, 'nombre' => 'ALFA FETOPROTEINA',                                'metodo' => 'ELISA Y CLIA', 'precio' => 60, 'subarea'=>'Marcadores Tumorales' ],
                    [ 'codigo' => 133, 'nombre' => 'ANTI BETA-2 MICROGLOBULINA',                       'metodo' => 'ELISA',        'precio' => 90,'subarea'=>'Marcadores Tumorales' ],
                    [ 'codigo' => 134, 'nombre' => 'CA 125',                                           'metodo' => 'ELISA Y CLIA', 'precio' => 60,'subarea'=>'Marcadores Tumorales' ],
                    [ 'codigo' => 135, 'nombre' => 'CA 15-3',                                          'metodo' => 'ELISA Y CLIA', 'precio' => 60,'subarea'=>'Marcadores Tumorales' ],
                    [ 'codigo' => 136, 'nombre' => 'CA 19-9',                                          'metodo' => 'ELISA Y CLIA', 'precio' => 60,'subarea'=>'Marcadores Tumorales' ],
                    [ 'codigo' => 137, 'nombre' => 'CA 2-50',                                          'metodo' => 'CLIA',         'precio' => 70,'subarea'=>'Marcadores Tumorales' ],
                    [ 'codigo' => 138, 'nombre' => 'CA 72-4',                                          'metodo' => 'CLIA',         'precio' => 70,'subarea'=>'Marcadores Tumorales' ],
                    [ 'codigo' => 139, 'nombre' => 'CEA',                                              'metodo' => 'ELISA Y CLIA', 'precio' => 70,'subarea'=>'Marcadores Tumorales' ],
                    [ 'codigo' => 140, 'nombre' => 'PSA LIBRE',                                        'metodo' => 'ELISA Y CLIA', 'precio' => 60,'subarea'=>'Marcadores Tumorales' ],
                    [ 'codigo' => 141, 'nombre' => 'PSA TOTAL',                                        'metodo' => 'ELISA Y CLIA', 'precio' => 60,'subarea'=>'Marcadores Tumorales' ],
//                    INMUNOGLOBULINAS Y FACTORES DE COMPLEMENTO
                    [ 'codigo' => 142, 'nombre' => 'C3 (QUIMICA SANGUÍNEA)',                           'metodo' => 'NEFELOMETRÍA', 'precio' => 60, 'subarea'=>'Inmunoglobulinas y Factores de Complemento' ],
                    [ 'codigo' => 143, 'nombre' => 'C4 (QUIMICA SANGUÍNEA)',                           'metodo' => 'NEFELOMETRÍA', 'precio' => 60, 'subarea'=>'Inmunoglobulinas y Factores de Complemento' ],
                    [ 'codigo' => 144, 'nombre' => 'INMUNOGLOBULINAS IgA',                             'metodo' => 'NEFELOMETRÍA', 'precio' => 60, 'subarea'=>'Inmunoglobulinas y Factores de Complemento' ],
                    [ 'codigo' => 145, 'nombre' => 'INMUNOGLOBULINAS IgM',                             'metodo' => 'NEFELOMETRÍA', 'precio' => 60, 'subarea'=>'Inmunoglobulinas y Factores de Complemento' ],
                    [ 'codigo' => 146, 'nombre' => 'INMUNOGLOBULINAS IgG',                             'metodo' => 'NEFELOMETRÍA', 'precio' => 60, 'subarea'=>'Inmunoglobulinas y Factores de Complemento' ],
                    [ 'codigo' => 147, 'nombre' => 'IgE ESPECIFICA TOTAL',                             'metodo' => 'ELISA',        'precio' => 100, 'subarea'=>'Inmunoglobulinas y Factores de Complemento' ],

                    [ 'codigo' => 149, 'nombre' => 'INSULINA (1 DETERMINACION)',                       'metodo' => 'ELISA Y CLIA', 'precio' => 60 , 'subarea'=>'Marcadores de Diabetes' ],
                    [ 'codigo' => 150, 'nombre' => 'PEPTIDO C',                                        'metodo' => 'ELISA',        'precio' => 160 , 'subarea'=>'Marcadores de Diabetes' ],

                    [ 'codigo' => 151, 'nombre' => 'TROPONINA I',                                      'metodo' => 'ELISA', 'precio' => 100, 'subarea'=>'Marcador de Daño Cardíaco' ],
//                    PERFIL FÉRRICO
                    [ 'codigo' => 152, 'nombre' => 'FERRITINA',                                        'metodo' => 'ELISA',        'precio' => 100, 'subarea'=>'Perfil Férrico' ],
                    [ 'codigo' => 153, 'nombre' => 'TRANSFERRINA',                                     'metodo' => 'NEFELOMETRÍA', 'precio' => 100 , 'subarea'=>'Perfil Férrico' ],

//                    VITAMINAS
                    [ 'codigo' => 154, 'nombre' => 'VITAMINA D',                                       'metodo' => 'ELISA/ CLIA', 'precio' => 250 , 'subarea'=>'Vitaminas' ],
                    [ 'codigo' => 155, 'nombre' => 'VITAMINA B 12',                                    'metodo' => 'ELISA',       'precio' => 180 , 'subarea'=>'Vitaminas' ],
//                    MARCADOR DE LA COAGULACIÓN
                    [ 'codigo' => 156, 'nombre' => 'DIMEROS D (PLASMA CITRATADO)',                     'metodo' => 'NEFELOMETRÍA', 'precio' => 200 , 'subarea'=>'Marcador de la Coagulación' ],
                ],
            ],

            // ---------------------------------------------------------
            // HORMONAS
            // ---------------------------------------------------------
//            [
//                'name' => 'HORMONAS',
//                'servicios' => [
//                    [ 'codigo' => 106, 'nombre' => 'ACTH (PLASMA CON EDTA)',                           'metodo' => 'ELISA Y CLIA', 'precio' => 200 ],
//                    [ 'codigo' => 107, 'nombre' => 'CORTISOL (2 TOMAS 8:00 Y 16:00)',                  'metodo' => 'ELISA Y CLIA', 'precio' => 120 ],
//                    [ 'codigo' => 108, 'nombre' => 'ESTRADIOL (ESTRÓGENOS)',                           'metodo' => 'ELISA Y CLIA', 'precio' => 100 ],
//                    [ 'codigo' => 109, 'nombre' => 'FSH (HORMONA FOLÍCULO ESTIMULANTE)',               'metodo' => 'ELISA Y CLIA', 'precio' => 100 ],
//                    [ 'codigo' => 110, 'nombre' => 'HGC (CUANTIFICACIÓN HORMONA GONADOTROFINA CORIÓNICA FRACCIÓN BETA)', 'metodo' => 'ELISA Y CLIA', 'precio' => 60 ],
//                    [ 'codigo' => 111, 'nombre' => 'HGH - HORMONA DEL CRECIMIENTO (1 Toma sin estimulación)', 'metodo' => 'ELISA Y CLIA', 'precio' => 100 ],
//                    [ 'codigo' => 112, 'nombre' => 'LH (HORMONA LUTENIZANTE)',                         'metodo' => 'ELISA Y CLIA', 'precio' => 100 ],
//                    [ 'codigo' => 113, 'nombre' => 'PROGESTERONA',                                     'metodo' => 'ELISA Y CLIA', 'precio' => 100 ],
//                    [ 'codigo' => 114, 'nombre' => 'PROLACTINA',                                       'metodo' => 'ELISA Y CLIA', 'precio' => 100 ],
//                    [ 'codigo' => 115, 'nombre' => 'PARATHORMONA',                                     'metodo' => 'ELISA Y CLIA', 'precio' => 100 ],
//                    [ 'codigo' => 116, 'nombre' => 'TSH',                                              'metodo' => 'ELISA Y CLIA', 'precio' => 100 ],
//                    [ 'codigo' => 117, 'nombre' => 'TSH NEONATAL (TAMIZAJE)',                          'metodo' => 'ELISA Y CLIA', 'precio' => 90 ],
//                    [ 'codigo' => 118, 'nombre' => 'T 3 TOTAL',                                        'metodo' => 'ELISA Y CLIA', 'precio' => 60 ],
//                    [ 'codigo' => 119, 'nombre' => 'T 4 TOTAL',                                        'metodo' => 'ELISA Y CLIA', 'precio' => 60 ],
//                    [ 'codigo' => 120, 'nombre' => 'T 4 LIBRE',                                        'metodo' => 'ELISA Y CLIA', 'precio' => 60 ],
//                    [ 'codigo' => 121, 'nombre' => 'TESTOSTERONA TOTAL',                               'metodo' => 'ELISA Y CLIA', 'precio' => 100 ],
//                    [ 'codigo' => 122, 'nombre' => 'TESTOSTERONA LIBRE',                               'metodo' => 'ELISA Y CLIA', 'precio' => 100 ],
//                    [ 'codigo' => 123, 'nombre' => 'TIROGLOBULINA',                                    'metodo' => 'ELISA Y CLIA', 'precio' => 90 ],
//                ],
//            ],
//
//            // ---------------------------------------------------------
//            // MARCADORES DE AUTOINMUNIDAD
//            // ---------------------------------------------------------
//            [
//                'name' => 'MARCADORES DE AUTOINMUNIDAD',
//                'servicios' => [
//                    [ 'codigo' => 124, 'nombre' => 'ANA (ANTICUERPOS ANTINUCLEARES)',                  'metodo' => 'ELISA', 'precio' => 90 ],
//                    [ 'codigo' => 125, 'nombre' => 'ANTI- DNA',                                        'metodo' => 'ELISA', 'precio' => 90 ],
//                    [ 'codigo' => 126, 'nombre' => 'ANTI-TPO (ANTI- PEROXIDASA)',                      'metodo' => 'ELISA Y CLIA', 'precio' => 90 ],
//                    [ 'codigo' => 127, 'nombre' => 'ANCA -P',                                          'metodo' => 'ELISA', 'precio' => 90 ],
//                    [ 'codigo' => 128, 'nombre' => 'ANCA -C',                                          'metodo' => 'ELISA', 'precio' => 90 ],
//                    [ 'codigo' => 129, 'nombre' => 'ANTI-TG (ANTI-TIROGLOBULINA)',                     'metodo' => 'ELISA Y CLIA', 'precio' => 60 ],
//                    [ 'codigo' => 130, 'nombre' => 'ANTI-CCP',                                         'metodo' => 'ELISA', 'precio' => 90 ],
//                    [ 'codigo' => 131, 'nombre' => 'PERIL ENA',                                        'metodo' => 'ELISA', 'precio' => 200 ],
//                ],
//            ],
//
//            // ---------------------------------------------------------
//            // MARCADORES TUMORALES
//            // ---------------------------------------------------------
//            [
//                'name' => 'MARCADORES TUMORALES',
//                'servicios' => [
//                    [ 'codigo' => 132, 'nombre' => 'ALFA FETOPROTEINA',                                'metodo' => 'ELISA Y CLIA', 'precio' => 60 ],
//                    [ 'codigo' => 133, 'nombre' => 'ANTI BETA-2 MICROGLOBULINA',                       'metodo' => 'ELISA',        'precio' => 90 ],
//                    [ 'codigo' => 134, 'nombre' => 'CA 125',                                           'metodo' => 'ELISA Y CLIA', 'precio' => 60 ],
//                    [ 'codigo' => 135, 'nombre' => 'CA 15-3',                                          'metodo' => 'ELISA Y CLIA', 'precio' => 60 ],
//                    [ 'codigo' => 136, 'nombre' => 'CA 19-9',                                          'metodo' => 'ELISA Y CLIA', 'precio' => 60 ],
//                    [ 'codigo' => 137, 'nombre' => 'CA 2-50',                                          'metodo' => 'CLIA',         'precio' => 70 ],
//                    [ 'codigo' => 138, 'nombre' => 'CA 72-4',                                          'metodo' => 'CLIA',         'precio' => 70 ],
//                    [ 'codigo' => 139, 'nombre' => 'CEA',                                              'metodo' => 'ELISA Y CLIA', 'precio' => 70 ],
//                    [ 'codigo' => 140, 'nombre' => 'PSA LIBRE',                                        'metodo' => 'ELISA Y CLIA', 'precio' => 60 ],
//                    [ 'codigo' => 141, 'nombre' => 'PSA TOTAL',                                        'metodo' => 'ELISA Y CLIA', 'precio' => 60 ],
//                ],
//            ],
//
//            // ---------------------------------------------------------
//            // INMUNOGLOBULINAS Y FACTORES DE COMPLEMENTO
//            // ---------------------------------------------------------
//            [
//                'name' => 'INMUNOGLOBULINAS Y FACTORES DE COMPLEMENTO',
//                'servicios' => [
//                    [ 'codigo' => 142, 'nombre' => 'C3 (QUIMICA SANGUÍNEA)',                           'metodo' => 'NEFELOMETRÍA', 'precio' => 60 ],
//                    [ 'codigo' => 143, 'nombre' => 'C4 (QUIMICA SANGUÍNEA)',                           'metodo' => 'NEFELOMETRÍA', 'precio' => 60 ],
//                    [ 'codigo' => 144, 'nombre' => 'INMUNOGLOBULINAS IgA',                             'metodo' => 'NEFELOMETRÍA', 'precio' => 60 ],
//                    [ 'codigo' => 145, 'nombre' => 'INMUNOGLOBULINAS IgM',                             'metodo' => 'NEFELOMETRÍA', 'precio' => 60 ],
//                    [ 'codigo' => 146, 'nombre' => 'INMUNOGLOBULINAS IgG',                             'metodo' => 'NEFELOMETRÍA', 'precio' => 60 ],
//                    [ 'codigo' => 147, 'nombre' => 'IgE ESPECIFICA TOTAL',                             'metodo' => 'ELISA',        'precio' => 100 ],
//                ],
//            ],
//
//            // ---------------------------------------------------------
//            // MARCADORES DE DIABETES
//            // ---------------------------------------------------------
//            [
//                'name' => 'MARCADORES DE DIABETES',
//                'servicios' => [
//                    [ 'codigo' => 149, 'nombre' => 'INSULINA (1 DETERMINACION)',                       'metodo' => 'ELISA Y CLIA', 'precio' => 60 ],
//                    [ 'codigo' => 150, 'nombre' => 'PEPTIDO C',                                        'metodo' => 'ELISA',        'precio' => 160 ],
//                ],
//            ],
//
//            // ---------------------------------------------------------
//            // MARCADOR DE DAÑO CARDÍACO
//            // ---------------------------------------------------------
//            [
//                'name' => 'MARCADOR DE DAÑO CARDÍACO',
//                'servicios' => [
//                    [ 'codigo' => 151, 'nombre' => 'TROPONINA I',                                      'metodo' => 'ELISA', 'precio' => 100 ],
//                ],
//            ],
//
//            // ---------------------------------------------------------
//            // PERFIL FÉRRICO
//            // ---------------------------------------------------------
//            [
//                'name' => 'PERFIL FÉRRICO',
//                'servicios' => [
//                    [ 'codigo' => 152, 'nombre' => 'FERRITINA',                                        'metodo' => 'ELISA',        'precio' => 100 ],
//                    [ 'codigo' => 153, 'nombre' => 'TRANSFERRINA',                                     'metodo' => 'NEFELOMETRÍA', 'precio' => 100 ],
//                ],
//            ],
//
//            // ---------------------------------------------------------
//            // VITAMINAS
//            // ---------------------------------------------------------
//            [
//                'name' => 'VITAMINAS',
//                'servicios' => [
//                    [ 'codigo' => 154, 'nombre' => 'VITAMINA D',                                       'metodo' => 'ELISA/ CLIA', 'precio' => 250 ],
//                    [ 'codigo' => 155, 'nombre' => 'VITAMINA B 12',                                    'metodo' => 'ELISA',       'precio' => 180 ],
//                ],
//            ],
//
//            // ---------------------------------------------------------
//            // MARCADOR DE LA COAGULACIÓN
//            // ---------------------------------------------------------
//            [
//                'name' => 'MARCADOR DE LA COAGULACIÓN',
//                'servicios' => [
//                    [ 'codigo' => 156, 'nombre' => 'DIMEROS D (PLASMA CITRATADO)',                     'metodo' => 'NEFELOMETRÍA', 'precio' => 200 ],
//                ],
//            ],

            // ---------------------------------------------------------
            // BIOLOGÍA MOLECULAR
            // ---------------------------------------------------------
            [
                'name' => 'BIOLOGÍA MOLECULAR (Area 7)',
                'title' => 'BIOLOGÍA MOLECULAR',
                'servicios' => [
                    [ 'codigo' => 157, 'nombre' => 'PCR-RT CON TOMA DE MUESTRA (PRUEBA PARA DIAGNOSTICO COVID-19)', 'metodo' => 'PCR -RT', 'precio' => 500 ],
                    [ 'codigo' => 158, 'nombre' => 'PRUEBA DE ELISA PARA COVID-19 (CUANTIFICACION DE ANTICUERPOS IgM e IgG)', 'metodo' => 'ELISA', 'precio' => 300 ],
                    [ 'codigo' => 159, 'nombre' => 'ANTIGENO NASAL (PRUEBA PARA DIAGNOSTICO COVID 19)', 'metodo' => 'MANUAL', 'precio' => 0 ], // GRATUITO SUS
                    [ 'codigo' => 160, 'nombre' => 'DIAGNOSTICO DE HPV (VIRUS PAPILOMA HUMANO)',       'metodo' => 'PCR -RT', 'precio' => 270 ],
                    [ 'codigo' => 161, 'nombre' => 'PANEL RESPIRATORIO',                               'metodo' => 'PCR -RT', 'precio' => 700 ,'descripcion'=>'- PCR EN TIEMPO REAL PANEL PARA DETECCION DE PATOGENOS RESPIRATORIOS <br>
     - PCR EN TIEMPO REAL PARA ADENOVIRUS <br>
     - PCR PANEL NEUMONIA DETECCION DE PATOGENOS '],
                    [ 'codigo' => 162, 'nombre' => 'PANEL SEXUAL',                                     'metodo' => 'PCR -RT', 'precio' => 1200, 'descripcion'=>'     - PCR EN TIEMPO REAL PANEL PARA DETECCION DE UREOPLASMA <br>
     - PCR EN TIEMPO REAL PARA LA DETECCION DE NEISSERIA GONORRHOEAE <br>
     - PCR PARA DETECCION DE MYCOPLASMA <br>
     - PCR PARA DETECCION DE SIFILIS <br>
     - PCR PARA DETECCION CHLAMYDIA TRACHOMATYS <br>
     - PCR PARA DETECCION DE HERPES SIMPLE 1 Y 2
     '],
                ],
            ],
        ];

        foreach ($areas as $a) {
            $area = Area::create([
                'name'        => $a['name'],
                'descripcion' => $a['name'],
                'estado'      => 'ACTIVO',
                'title'       => $a['title'] ?? $a['name'],
            ]);

            foreach ($a['servicios'] as $s) {
                Servicio::create([
                    'area_id' => $area->id,
                    'codigo'  => $s['codigo'] ?? null,
                    'nombre'  => $s['nombre'],
                    'metodo'  => $s['metodo'] ?? null,
                    'precio'  => $s['precio'] ?? 0,
                    'estado'  => 'ACTIVO',
                    'subarea' => $s['subarea'] ?? null,
                    'descripcion' => $s['descripcion'] ?? null,
                ]);
            }
        }
    }
}
