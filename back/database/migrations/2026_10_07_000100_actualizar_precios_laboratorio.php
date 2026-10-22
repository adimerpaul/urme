<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Nuevos precios de laboratorio (octubre 2026). Actualiza el precio normal de
 * los exámenes que ya existen, identificados por id + nombre exacto. Antes de
 * tocar nada verifica que todos los exámenes existan con ese nombre; si alguno
 * no coincide, aborta sin cambiar ningún precio. No crea productos ni toca el
 * precio de seguro. El down() restaura el precio anterior.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->aplicar(fn (array $fila) => $fila[3]);
    }

    public function down(): void
    {
        $this->aplicar(fn (array $fila) => $fila[2]);
    }

    private function aplicar(callable $precio): void
    {
        $filas = $this->precios();

        $existentes = DB::table('productos')
            ->whereNull('deleted_at')
            ->whereIn('id', array_column($filas, 0))
            ->pluck('nombre', 'id');

        $faltantes = array_filter($filas, fn (array $fila) => ($existentes[$fila[0]] ?? null) !== $fila[1]);

        if ($faltantes) {
            $detalle = implode(', ', array_map(fn (array $fila) => "#{$fila[0]} {$fila[1]}", $faltantes));
            throw new RuntimeException("Exámenes no encontrados o renombrados, no se actualizó ningún precio: {$detalle}");
        }

        DB::transaction(function () use ($filas, $precio) {
            foreach ($filas as $fila) {
                DB::table('productos')
                    ->where('id', $fila[0])
                    ->where('nombre', $fila[1])
                    ->update(['precio' => $precio($fila), 'updated_at' => now()]);
            }
        });
    }

    /** [id, nombre en el sistema, precio anterior, precio nuevo] */
    private function precios(): array
    {
        return [
            // HEMATOLOGIA
            [779, 'HEMOGRAMA (INDICES HEMATIMÉTRICOS + PLAQUETAS)', 30.00, 37.00],
            [784, 'HEMOGLOBINA + HEMATOCRITO', 10.00, 16.00],
            [781, 'PLAQUETAS', 15.00, 22.00],
            [782, 'RETICULOCITOS + IPR', 15.00, 22.00],
            [780, 'GRUPO SANGUÍNEO Y FACTOR RH', 20.00, 25.00],
            [834, 'COOMBS DIRECTO', 30.00, 37.00],
            [835, 'COOMBS INDIRECTO', 35.00, 45.00],
            [836, 'VES-VSG, VELOCIDAD DE ERITROSEDIMENTACION GLOBULAR.', 10.00, 15.00],
            [837, 'CELULAS L.E.', 125.00, 155.00],
            [838, 'GOTA GRUESA', 15.00, 22.00],
            [839, 'FROTIS DE SANGRE PERIFERICA (UNA SOLA MUESTRA)', 20.00, 25.00],
            // COAGULOGRAMA
            [786, 'TIEMPO DE SANGRÍA + TIEMPO DE COAGULACION', 10.00, 15.00],
            [787, 'TIEMPO DE PROTROMBINA - TP - % ACT - INR', 35.00, 45.00],
            [840, 'TIEMPO TROMBOPLASTINA PARCIAL - APTT', 55.00, 60.00],
            [841, 'TIEMPO DE TROMBINA - TT', 40.00, 45.00],
            [842, 'FIBRINOGENO', 85.00, 90.00],
            [843, 'DIMERO D', 120.00, 145.00],
            // ESPECIALES
            [844, 'ANTICOAGULANTE LÚPICO', 150.00, 170.00],
            // COAGULOGRAMA
            [845, 'FACTOR V', 340.00, 450.00],
            // QUIMICA SANGUINEA
            [788, 'GLICEMIA EN AYUNAS', 20.00, 32.00],
            [789, 'GLICEMIA POST-PRANDIAL', 20.00, 32.00],
            [846, 'GLUCOSA POSTPRANDIAL (0-2 HORAS)', 50.00, 60.00],
            [847, 'GLUCOSA CON CARGA DE GLUCOSA (3 TOMAS DE MUESTRA)', 100.00, 145.00],
            [783, 'HEMOGLOBINA GLICOSILADA HBA1C', 75.00, 90.00],
            [848, 'CREATININA', 20.00, 32.00],
            [849, 'UREA /NUS-BUN', 20.00, 35.00],
            [850, 'ACIDO URICO', 20.00, 32.00],
            [851, 'AMILASA', 55.00, 70.00],
            [852, 'LIPASA', 90.00, 110.00],
            [853, 'LACTATO DESHIDROGENASA', 45.00, 55.00],
            [854, 'COLESTEROL TOTAL', 20.00, 35.00],
            [855, 'TRIGLICERIDOS', 20.00, 35.00],
            [856, 'HDL-LDL- VLDLCOLESTEROL', 30.00, 35.00],
            [857, 'ACIDOS BILIARES', 300.00, 350.00],
            [858, 'TRANSAMINASAS (ALT-GPT)', 20.00, 35.00],
            [859, 'TRANSAMINASAS (AST-GOT)', 20.00, 35.00],
            [860, 'FOSFATASA ALCALINA', 20.00, 35.00],
            [861, 'BILIRRUBINA TOTAL Y FRACCIONES', 30.00, 45.00],
            [862, 'GGT GAMA GLUTAMIL TRANSFERASA', 35.00, 45.00],
            [863, 'PROTEINAS TOTALES', 20.00, 37.00],
            [864, 'ALBUMINA-GLOBULINA- REL A/G', 20.00, 40.00],
            [865, 'ELECTROFORESIS DE PROTEINAS', 340.00, 400.00],
            // IONOGRAMA / ELECTROLITOS
            [866, 'SODIO', 43.00, 50.00],
            [867, 'CLORO', 43.00, 50.00],
            [868, 'POTASIO', 43.00, 50.00],
            [869, 'CALCIO IONICO', 43.00, 50.00],
            [870, 'CALCIO TOTAL', 43.00, 50.00],
            [871, 'MAGNESIO', 43.00, 50.00],
            [872, 'FOSFORO', 43.00, 50.00],
            // CINETICA DE HIERRO
            [873, 'HIERRO SERICO', 55.00, 68.00],
            [874, 'TIBC+TRANSFERRINA+% DE SATURACION', 100.00, 150.00],
            [875, 'FERRITINA', 120.00, 140.00],
            // PERFIL CARDIACO
            [876, 'TROPONINA CUANTIFICADA', 110.00, 145.00],
            [877, 'CK-MB', 90.00, 100.00],
            [878, 'CK-TOTAL', 60.00, 100.00],
            [880, 'PRO BNP', 140.00, 150.00],
            [881, 'NT-PROBNP (NT-PROPÉPTIDO NATRIURÉTICO CEREBRAL)', 130.00, 150.00],
            [882, 'NT-PROBNP (NT-PROPÉPTIDO NATRIURÉTICO AURICULAR)', 130.00, 150.00],
            [883, 'NT-PROBNP (NT-PROPÉPTIDO NATRIURÉTICO CENTRAL)', 130.00, 150.00],
            // CITOQUIMICOS
            [884, 'CITOQUÍMICO DE LÍQUIDO SINOVIAL', 150.00, 200.00],
            [885, 'CITOQUÍMICO DE LÍQUIDO ASCÍTICO O PERITONEAL', 150.00, 200.00],
            [886, 'CITOQUÍMICO DE LÍQUIDO PLEURAL)', 150.00, 200.00],
            [887, 'CITOQUÍMICO DE LÍQUIDO PERICÁRDICO', 150.00, 200.00],
            [888, 'CITOQUÍMICO DE LCR', 150.00, 200.00],
            // ESPECIALES
            [889, 'TINTA CHINA', 100.00, 120.00],
            // UROANALISIS
            [796, 'EXAMEN GENERAL DE ORINA - EGO', 20.00, 27.00],
            [890, 'PROTEINURIA 24 HORAS', 30.00, 40.00],
            [891, 'DEPURACIÓN O CLEARENCE DE CREATININA', 40.00, 60.00],
            [892, 'OSMOLARIDAD URINARIA', 140.00, 160.00],
            [893, 'UREA/NUS-BUN URINARIA', 20.00, 32.00],
            [894, 'ÁCIDO ÚRICO URINARIO', 20.00, 32.00],
            [895, 'CREATINURIA (CREATININA EN ORINA)', 20.00, 32.00],
            [898, 'GLUCOSURIA', 20.00, 32.00],
            [899, 'AMILASA EN ORINA', 55.00, 70.00],
            // QUIMICA SANGUINEA
            [900, 'FENA', 95.00, 110.00],
            [901, 'EFU', 95.00, 110.00],
            [902, 'FAUA', 95.00, 110.00],
            // COPROANALISIS
            [903, 'COPROPARASITOLOGICO SIMPLE - HECES RUTINA', 15.00, 20.00],
            [791, 'COPROPARASITOLOGICO SERIADO', 55.00, 60.00],
            [792, 'MOCO FECAL', 15.00, 20.00],
            [904, 'HELICOBACTER PYLORI EN HECES (PRUEBA RÁPIDA)', 80.00, 98.00],
            [905, 'SANGRE OCULTA (PRUEBA RAPIDA)', 45.00, 90.00],
            [906, 'SANGRE OCULTA (PRUEBA RAPIDA/SERIADO)', 135.00, 260.00],
            [907, 'AZUCARES REDUCTORES (REACCION DE BENEDICT)', 35.00, 45.00],
            [908, 'PH EN HECES', 15.00, 20.00],
            [909, 'TEST DE GRAHAM', 15.00, 20.00],
            [910, 'TINCION GRAM EN HECES SIMPLE', 20.00, 25.00],
            [911, 'TINCIÓN GRAM EN HECES (SERIADO)', 60.00, 75.00],
            [912, 'TEST DE GRAHAM SERIADO', 45.00, 60.00],
            [913, 'ROTAVIRUS', 80.00, 100.00],
            [914, 'ADENOVIRUS', 80.00, 100.00],
            [915, 'HELICOBACTER PYLORI EN HECES (ELISA)', 100.00, 150.00],
            [916, 'GIARDIA (ELISA)', 120.00, 150.00],
            [897, 'GIARDIA ( ELISA)', 120.00, 150.00],
            [917, 'AMEBA HISTOLITICA (ELISA)', 120.00, 150.00],
            [918, 'CALPROTECTINA FECAL', 125.00, 140.00],
            // SECRECIONES
            [919, 'TEST DE CRISTALIZACIÓN - TEST DE HELECHOS', 15.00, 20.00],
            [795, 'EXAMEN EN FRESCO MAS PH, CELULAS CLAVE, AMINAS', 40.00, 45.00],
            [794, 'TINCIÓN GRAM (ESPUTO,SECRESIONES)', 20.00, 25.00],
            // SEROLOGIAS
            [920, 'PROTEÍNA C REACTIVA - PCR - CRP(CUANTIFICADO)', 65.00, 70.00],
            [921, 'FACTOR REUMATOIDE (LATEX) - FR', 30.00, 40.00],
            [922, 'ASTO - ASO - ANTIESTREPTOLISINA O', 30.00, 40.00],
            [923, 'RPR/SIFILIS/VDRL (FLOCULACIÓN)', 30.00, 50.00],
            [924, 'REACCIÓN DE WIDAL (ANTIGENOS A, B,O,H)', 35.00, 44.00],
            [925, 'HELICOBACTER PYLORI EN SANGRE (PRUEBA RÁPIDA )', 55.00, 75.00],
            [926, 'HEPATITIS A IGG + IGM (PRUEBA RÁPIDA)', 100.00, 130.00],
            [927, 'HEPATITIS B (PRUEBA RÁPIDA)', 90.00, 100.00],
            [928, 'HEPATITIS C (PRUEBA RÁPIDA)', 90.00, 100.00],
            [929, 'VIH 1/2 - HIV PRUEBA RAPIDA', 60.00, 75.00],
            [930, 'TEST DE EMBARAZO EN SANGRE/HCG/PREGNOSTICON', 25.00, 40.00],
            // INMUNOLOGIA
            [932, 'CHAGAS HAI', 90.00, 100.00],
            [933, 'TOXOPLASMOSIS HAI', 90.00, 100.00],
            [935, 'PROCALCITONINA IFI', 120.00, 145.00],
            // GASOMETRIAS
            [778, 'GASOMETRIA ARTERIAL/ELECTROLITOS (EG7)', 350.00, 400.00],
            [936, 'GASOMETRIA ARTERIAL CON LACTATO (CG4)', 400.00, 450.00],
            // PERFIL TIROIDEO
            [937, 'T3 TOTAL', 100.00, 125.00],
            [938, 'T4 TOTAL', 100.00, 125.00],
            [939, 'TSH ULTRASENSIBLE (CLIA)', 100.00, 150.00],
            [940, 'TSH - TIROTROPINA(CLIA)', 100.00, 150.00],
            [941, 'T4 LIBRE', 100.00, 125.00],
            [942, 'T3 LIBRE', 100.00, 125.00],
            // INMUNOLOGIA
            [943, 'ANTI-TIROGLOBULINA / ANTI TG', 140.00, 160.00],
            [944, 'ANTI- PEROXIDASA / ANTI TPO', 140.00, 160.00],
            [945, 'TIROGLOBULINA', 140.00, 160.00],
            // PESQUIZA NEONATAL
            [946, 'TSH-NEONATAL (HIPOTIROIDISMO CONGENITO)', 130.00, 160.00],
            [1031, 'TIROTROFINA NEONATAL - TSH NEONATAL (HIPOTIROIDISMO CONGENITO)', 120.00, 160.00],
            // INMUNOLOGIA
            [947, 'ANTI RECEPTOR TSH', 250.00, 270.00],
            // FERTILIDAD - INMUNOLOGIA
            [948, 'ANTIMULLERIANA-AMH (IFI)', 150.00, 200.00],
            [949, 'FSH - HORMONA FOLÍCULO ESTIMULANTE (IFI)', 100.00, 140.00],
            [950, 'LH - HORMONA LUTEINIZANTE (IFI)', 100.00, 140.00],
            [951, 'PROLACTINA (IFI)', 100.00, 125.00],
            [952, 'ESTRADIOL (IFI)', 100.00, 125.00],
            [953, 'PROGESTERONA (IFI)', 100.00, 140.00],
            [954, 'TESTOSTERONA TOTAL (IFI)', 100.00, 160.00],
            [955, 'TESTOSTERONA LIBRE', 100.00, 150.00],
            // INMUNOLOGIA
            [956, '17-HIDROXIPROGESTERONA', 200.00, 250.00],
            [957, 'DHEA-S / DEHIDROEPIANDROSTERONA SULFATO', 110.00, 180.00],
            [958, 'DHEA / DEHIDROEPIANDROSTERONA', 110.00, 180.00],
            [959, 'ESTRIOL LIBRE', 220.00, 250.00],
            [960, 'ESTRIOL', 200.00, 250.00],
            [961, 'SHBG- GLOBULINA FIJADORA DE HORMONAS SEXUALES', 200.00, 260.00],
            // FERTILIDAD - INMUNOLOGIA
            [962, 'ANDROSTENEDIONA', 200.00, 250.00],
            // HORMONAS - INMUNOLOGIA
            [963, 'CORTISOL (IFI)', 100.00, 130.00],
            [964, 'CORTISOL (AM Y PM)(IFI)', 200.00, 260.00],
            [965, 'IGF-1', 200.00, 250.00],
            // INMUNOLOGIA
            [966, 'INSULINA (IFI)', 120.00, 150.00],
            [967, 'PARATOHORMONA-PTH', 150.00, 175.00],
            [968, 'GH- HORMONA DEL CRECIMIENTO', 140.00, 165.00],
            [969, 'PÉPTIDO C', 150.00, 190.00],
            // HORMONAS - INMUNOLOGIA
            [970, 'ERITROPOYETINA', 180.00, 200.00],
            // INMUNOLOGIA
            [971, 'ALDOSTERONA', 270.00, 300.00],
            [972, 'ADH - HORMONA ANTIDIURÉTICA', 330.00, 360.00],
            [973, 'CALCITONINA', 250.00, 270.00],
            [974, 'ACTH - ADRENOCORTICOTROPINA', 150.00, 180.00],
            // AUTOINMUNES - INMUNOLOGIA
            [975, 'ANTI - PCC (IFI)', 200.00, 225.00],
            [976, 'ANA (ANTICUERPOS ANTINUCLEARES)', 150.00, 220.00],
            [977, 'ANTI-DNA DS (CLIA)', 150.00, 200.00],
            [978, 'ANTI-DNA SS (CLIA)', 150.00, 200.00],
            [979, 'PERFIL ENA (SS-A, SS-B, SCL-70, JO-1, RNP, SM)', 650.00, 750.00],
            [980, 'C-ANCA / PR3', 150.00, 185.00],
            [981, 'P-ANCA / MPO', 150.00, 185.00],
            [982, 'COMPLEMENTO C4', 90.00, 115.00],
            [983, 'COMPLEMENTO C3', 90.00, 115.00],
            [984, 'COMPLEMENTO TOTAL CH50', 250.00, 300.00],
            [985, 'INMUNOGLOBULINA IGA', 130.00, 170.00],
            [986, 'INMUNOGLOBULINA IGG', 130.00, 170.00],
            [987, 'INMUNOGLOBULINA IGM', 130.00, 170.00],
            [988, 'INMUNOGLOBULINA IGE (IFI)', 130.00, 170.00],
            [989, 'INTERLEUCINAS', 120.00, 150.00],
            // PANEL ALERGICO
            [990, 'PANEL ALÉRGICO PEDIÁTRICO', 550.00, 700.00],
            [991, 'PANEL ALÉRGICO ALIMENTICIO', 550.00, 750.00],
            [992, 'PANEL ALÉRGICO RESPIRATORIO', 550.00, 750.00],
            [993, 'PANEL ALERGENOS PEDIATRICOS', 550.00, 700.00],
            // HEMATOLOGIA
            [994, 'ANTICUERPOS ANTIPLAQUETARIOS', 320.00, 350.00],
            // INMUNOLOGIA
            [995, 'ACS ANTIFOSFOLIPIDICOS IGG-IGM', 180.00, 300.00],
            [996, 'ANTICARDIOLIPINA IGG-IGM', 180.00, 300.00],
            [997, 'ANTI - B2-GLICOPROTEÍNA I IGG/IGM', 400.00, 450.00],
            [998, 'ANTI GLIADINA IGG/IGA', 450.00, 500.00],
            [999, 'ANTI TRANSGLUTAMINASA IGG/IGA', 450.00, 500.00],
            [1000, 'ANTI ENDOMISIO IGG/IGA', 450.00, 500.00],
            [1001, 'ANTICUERPOS ANTIMITOCONDRIALES - AMA', 200.00, 250.00],
            [1002, 'ANTICUERPOS ANTIMUSCULO LISO - MSA - ASMA', 200.00, 260.00],
            // AUTOINMUNES - INMUNOLOGIA
            [1003, 'ANTICUERPOS TIPO 1 MICROSOMALES DE HÍGADO Y RIÑÓN - LKM-1', 250.00, 280.00],
            // MARCADORES TUMORALES - INMUNOLOGIA
            [1004, 'ALFA FETO PROTEÍNA AFP (IFI)', 100.00, 130.00],
            [1005, 'ANTÍGENO CARCINO EMBRIONARIO CEA (IFI)', 100.00, 130.00],
            [1006, 'CA-15.3', 110.00, 130.00],
            [1007, 'CA-19.9 (IFI)', 110.00, 130.00],
            [1008, 'CA-125 (IFI)', 110.00, 130.00],
            [1009, 'CA-127', 110.00, 150.00],
            [1010, 'CA 72.4', 110.00, 150.00],
            // INMUNOLOGIA
            [1011, 'B-HCG CUANTITATIVO', 120.00, 100.00],
            [1012, 'B-2 MICROGLOBULINA', 180.00, 200.00],
            // MARCADORES TUMORALES - INMUNOLOGIA
            [1013, 'PSA LIBRE', 120.00, 145.00],
            [1014, 'PSA TOTAL (CLIA)', 120.00, 145.00],
            [1015, 'LEPTINA', 400.00, 420.00],
            // INFECCIOSOS (ELISA) - INMUNOLOGIA
            [1016, 'TOXOPLASMOSIS IGG IGM', 200.00, 230.00],
            [1017, 'RUBEOLA IGG-IGM', 200.00, 230.00],
            [1018, 'CITOMEGALOVIRUS IGG-IGM', 200.00, 230.00],
            // INMUNOLOGIA
            [931, 'TORCH IGG-IGM', 900.00, 950.00],
            // INFECCIOSOS (ELISA) - INMUNOLOGIA
            [1019, 'CLAMIDIA IGG-IGM', 200.00, 230.00],
            [1020, 'HERPES ZOSTER IGG-IGM', 250.00, 300.00],
            [1021, 'HERPES VIRUS SIMPLEX 1 IGG-IGM', 200.00, 250.00],
            [1022, 'HERPES VIRUS SIMPLEX 2 IGG-IGM', 200.00, 250.00],
            [1023, 'VIRUS DE EPSTEIN BARR IGG-IGM', 250.00, 300.00],
            [1024, 'HELICOBACTER  PYLORI IGG-IGM EN SANGRE', 200.00, 230.00],
            [1025, 'HIDATIDOSIS IGG', 150.00, 200.00],
            [1026, 'CISTICERCOSIS EIA', 200.00, 250.00],
            [1027, 'CHAGAS (ELISA)', 100.00, 130.00],
            [1028, 'HEPATITIS A IGG/IGM', 140.00, 180.00],
            [1029, 'HEPATITIS B IGG-IGM', 110.00, 160.00],
            [1030, 'HEPATITIS C IGG / IGM', 110.00, 160.00],
            // VITAMINAS-INMUNOLOGIA
            [1032, 'VITAMINA B 9 - ÁCIDO FÓLICO', 190.00, 220.00],
            [1033, 'VITAMINA B 12 - COBALAMINA', 190.00, 225.00],
            [1034, 'VITAMINA B 2 - RIBOFLAVINA', 400.00, 425.00],
            [1035, 'VITAMINA B 6 - PIRIDOXINA', 400.00, 425.00],
            [1036, 'VITAMINA D3 - COLECALCIFEROL', 190.00, 250.00],
            // INMUNOLOGIA
            [1038, 'INTERLEUQUINA 6 -- IL-6', 260.00, 300.00],
            // ESPECIALES
            [1039, 'COVID-19 + INFLUENZA + SINSITIAL', 100.00, 120.00],
            // BACTERIOLOGIA
            [1041, 'UROCULTIVO +ATB', 130.00, 160.00],
            [1042, 'COPROCULTIVO + ATB', 130.00, 160.00],
            [1043, 'ESPERMOCULTIVO + ATB', 130.00, 160.00],
            [1044, 'CULTIVO SECRECION VAGINAL + ATB', 130.00, 160.00],
            [1045, 'CULTIVO SECRECION URETRAL + ATB', 130.00, 160.00],
            [1046, 'CULTIVO DE ESPUTO + ATB', 130.00, 160.00],
            [1047, 'CULTIVO HISOPADO FARINGEO + ATB', 130.00, 160.00],
            [1048, 'CULTIVO DE ABCESOS / SECRECIONES/VARIOS + ATB', 130.00, 160.00],
            [1049, 'CULTIVO DE PUNTA DE CATETER', 130.00, 150.00],
            [1050, 'HEMOCULTIVO', 150.00, 180.00],
            // BACTERIOSCOPIA
            [1052, 'BACILOSCOPIA SIMPLE EN ESPUTO - BK ESPUTO', 25.00, 35.00],
            [1053, 'BACILOSCOPIA SERIADA EN ESPUTO 3 MUESTRAS)', 75.00, 105.00],
            // BACTERIOLOGIA
            [1054, 'CULTIVO DE SUPERFICIES,AMBIENTES U OTROS', 130.00, 160.00],
            // DROGAS DE ABUSO
            [1055, 'PANEL DE DROGAS (11 PARAMETROS)', 350.00, 450.00],
            // BIOLOGIA MOLECULAR
            [1058, 'VIRUS DEL PAPILOMA HUMANO VHP: 21 GENOTIPOS', 700.00, 800.00],
            [1059, 'PANEL DE TRANSMISION SEXUAL T12', 750.00, 800.00],
            [1060, 'PROSTATITIS BACTERIANA G10', 850.00, 900.00],
            [1061, 'PANEL RESPIRATORIO G3: SARVS-COV 2, INFLUENZA A, INFLUENZA B', 550.00, 750.00],
            [1062, 'PANEL RESPIRATORIO G16', 950.00, 1000.00],
            [1063, 'TUBERCULOSIS : MICOBACTERIUM TUBERCULOSIS', 550.00, 600.00],
            [1064, 'PANEL DE DETECCION DE MENINGITIS BACTERIANA G3', 550.00, 650.00],
            [1065, 'PANEL DE DETECCION DE MININGITIS BACTERIANA D6', 900.00, 1000.00],
            [1066, 'PANEL DE DETECCION DE MENINGITIS VIRAL G4', 750.00, 850.00],
            [1067, 'PANEL GASTRICO G9', 900.00, 950.00],
            // GENETICA
            [1068, 'ADN (PATERNIDAD 2 MUESTRAS PADRE E HIJO)', 2200.00, 2500.00],
            [1069, 'ADN (PATERNIDAD 3 MUESTRAS PADRE, MADRE E HIJO)', 2700.00, 3000.00],
            [1070, 'ADN (PATERNIDAD 2 MUESTRAS PADRE E HIJO) - CABELLO/UÑAS', 2500.00, 2800.00],
            [1071, 'ADN (PATERNIDAD 2 MUESTRAS PADRE E HIJO) EXPRESS', 3200.00, 3500.00],
            // ANATOMIA PATOLOGICA
            [1056, 'PAP - PAPANICOLAU', 90.00, 115.00],
        ];
    }
};
