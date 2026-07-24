<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('datos_quimica_sanguinea', function (Blueprint $table) {
            $table->id();
            $table->string('variable')->unique();      // nombre del campo en quimica_sanguineas (ej. glucosa)
            $table->string('nombre');                  // etiqueta visible (ej. Glucosa)
            $table->string('seccion')->nullable();     // sección del formulario
            $table->integer('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('datos_quimica_sanguinea_prestacion', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dato_quimica_sanguinea_id');
            $table->foreign('dato_quimica_sanguinea_id', 'dato_quimica_prestacion_dato_fk')
                ->references('id')->on('datos_quimica_sanguinea')->cascadeOnDelete();
            $table->foreignId('servicio_id')->constrained('servicios')->cascadeOnDelete();
            $table->unique(['dato_quimica_sanguinea_id', 'servicio_id'], 'dato_quimica_servicio_unique');
            $table->timestamps();
        });

        $this->seedDatos();
        $this->seedPermiso();
    }

    public function down(): void
    {
        Schema::dropIfExists('datos_quimica_sanguinea_prestacion');
        Schema::dropIfExists('datos_quimica_sanguinea');

        $permiso = DB::table('permissions')->where('name', 'Configuración química sanguínea')->first();
        if ($permiso) {
            DB::table('model_has_permissions')->where('permission_id', $permiso->id)->delete();
            DB::table('permissions')->where('id', $permiso->id)->delete();
        }
    }

    protected function seedDatos(): void
    {
        // Mapeo tomado de los v-if por nombre de servicio que había en QuimicaSanguinia.vue.
        // Sin prestaciones asignadas = la variable se muestra siempre.
        $perfilRenal = 'PERFIL RENAL (CREATININA SÉRICA, ÁCIDO ÚRICO, UREA)';
        $proteinograma = 'PROTEINOGRAMA (PROTEÍNAS TOTALES, ALBÚMINA, GLOBULINA)';
        $perfilHepatico = 'PERFIL HEPÁTICO O HEPATOGRAMA (BILIRRUBINAS TOTALES Y FRACCIONADAS, FOSFATASA ALCALINA, GOT, GPT, GGT, TP)';
        $perfilLipidico = 'PERFIL LIPÍDICO O LIPIDOGRAMA (COLESTEROL, TRIGLICERIDOS, HDLc,LDLc,VLDLc)';
        $hdlLdl = 'HDLc, LDLc, VLDLc';
        $electrolitos = 'ELECTROLITOS (SODIO, POTASIO, CLORO)';
        $ionograma = 'IONOGRAMA (NA,K,CL,CA,Mg,P)';
        $bilirrubinas = ['BILIRRUBINAS TOTALES Y FRACCIONADAS', $perfilHepatico];
        $ptg = ['PRUEBA DE TOLERANCIA A LA GLUCOSA (3 MEDICIONES) (PTG)', 'PRUEBA DE TOLERANCIA A LA GLUCOSA (4 MEDICIONES) (PTG)'];
        $citoquimico = ['CITOQUÍMICO LÍQUIDO CEFALORRAQUÍDEO Y OTROS LÍQUIDOS'];
        $widal = ['REACCIÓN DE WIDAL'];
        $gasometria = ['GASOMETRÍA ARTERIAL O VENOSA', 'GASOMETRÍA', 'GASOMETRIA'];

        $datos = [
            ['acido_urico', 'Ácido úrico', 'Química sanguínea básica', ['ÁCIDO ÚRICO', $perfilRenal]],
            ['albumina', 'Albúmina', 'Química sanguínea básica', ['ALBUMINA', $proteinograma]],
            ['proteinas_totales', 'Proteínas totales', 'Química sanguínea básica', ['PROTEINAS TOTALES', $proteinograma]],
            ['glucosa', 'Glucosa', 'Química sanguínea básica', array_merge(['GLICEMIA (GLUCOSA)', 'GLUCOSA'], $ptg)],
            ['urea', 'Urea', 'Química sanguínea básica', ['UREA', $perfilRenal, 'NITROGENO UREICO SERICO (NUS)']],
            ['nus', 'NUS', 'Química sanguínea básica', ['NITROGENO UREICO SERICO (NUS)']],
            ['creatinina', 'Creatinina', 'Química sanguínea básica', ['CREATININA SÉRICA', $perfilRenal, 'CLEARENCE DE CREATININA']],
            ['globulina', 'Globulina', 'Química sanguínea básica', [$proteinograma]],
            ['relacion_ag', 'Relación A/G', 'Química sanguínea básica', [$proteinograma]],

            ['bilirrubina_total', 'Bilirrubina Total', 'Enzimas hepáticas y bilirrubinas', $bilirrubinas],
            ['bilirrubina_directa', 'Bilirrubina Directa', 'Enzimas hepáticas y bilirrubinas', $bilirrubinas],
            ['bilirrubina_indirecta', 'Bilirrubina Indirecta', 'Enzimas hepáticas y bilirrubinas', $bilirrubinas],
            ['got', 'G.O.T. (TGO)', 'Enzimas hepáticas y bilirrubinas', ['TRANSAMINASAS GOT- (ALT)', $perfilHepatico]],
            ['gpt', 'G.P.T. (TGP)', 'Enzimas hepáticas y bilirrubinas', ['TRANSAMINASAS GPT', $perfilHepatico]],
            ['fosfatasa_alcalina', 'Fosfatasa Alcalina', 'Enzimas hepáticas y bilirrubinas', ['FOSFATASA ALCALINA', $perfilHepatico]],
            ['ggt', 'GGT', 'Enzimas hepáticas y bilirrubinas', ['GAMA GLUTAMIL TRANSFERASA (GGT)', $perfilHepatico]],
            ['amilasa', 'Amilasa', 'Enzimas hepáticas y bilirrubinas', ['AMILASA']],

            ['trigliceridos', 'Triglicéridos', 'Perfil lipídico', [$hdlLdl, 'TRIGLICÉRIDOS', $perfilLipidico]],
            ['colesterol_total', 'Colesterol total', 'Perfil lipídico', [$hdlLdl, 'COLESTEROL', $perfilLipidico]],
            ['hdl_colesterol', 'HDL', 'Perfil lipídico', [$hdlLdl, $perfilLipidico]],
            ['ldl_colesterol', 'LDL', 'Perfil lipídico', [$hdlLdl, $perfilLipidico]],
            ['vldl_colesterol', 'VLDL', 'Perfil lipídico', [$hdlLdl, $perfilLipidico]],

            ['sodio', 'Sodio', 'Electrolitos y minerales', [$electrolitos, $ionograma]],
            ['potasio', 'Potasio', 'Electrolitos y minerales', [$electrolitos, $ionograma]],
            ['cloro', 'Cloro', 'Electrolitos y minerales', [$electrolitos, $ionograma]],
            ['calcio', 'Calcio', 'Electrolitos y minerales', ['CALCIO', $ionograma]],
            ['fosforo', 'Fósforo', 'Electrolitos y minerales', ['FÓSFORO', $ionograma]],
            ['magnesio', 'Magnesio', 'Electrolitos y minerales', ['MAGNESIO', $ionograma]],
            ['hierro_serico', 'Hierro sérico', 'Electrolitos y minerales', ['HIERRO']],
            ['trf', 'Transferrina (TRF)', 'Electrolitos y minerales', ['HIERRO', 'TRANSFERRINA']],

            ['creatinuria_24h', 'Creatinuria 24 hrs.', 'Química básica en orina', ['CREATININA EN ORINA (CREATINURIA)', 'CLEARENCE DE CREATININA']],
            ['creatinuria_casual', 'Creatinuria Casual', 'Química básica en orina', ['PROTEINURIA 24 HRS', 'CREATININA EN ORINA (CREATINURIA)']],
            ['proteinuria_24h', 'Proteinuria de 24 hrs.', 'Química básica en orina', ['PROTEINURIA 24 HRS']],
            ['volumen_24h', 'Volumen 24 h', 'Química básica en orina', ['PROTEINURIA 24 HRS', 'CREATININA EN ORINA (CREATINURIA)', 'CLEARENCE DE CREATININA']],
            ['dce', 'DCE (Depuración de Creatinina)', 'Química básica en orina', ['CLEARENCE DE CREATININA']],
            ['microalbuminuria', 'Microalbuminuria', 'Química básica en orina', ['MICROALBUMINURIA']],

            ['ck_total', 'CK-Total', 'Otros', ['CK TOTAL']],
            ['ck_mb', 'CK MB', 'Otros', ['CK MB']],
            ['ldh', 'LDH', 'Otros', ['LACTATO DESHIDROGENASA ( LDH )']],
            ['lipasa', 'Lipasa', 'Otros', ['LIPASA']],

            ['hb_glicosilada', 'Hb Glicosilada A1C', 'Control glucémico', ['HEMOGLOBINA GLICOSILADA A1c']],

            ['aso', 'ASO O ASTO', 'Pruebas serológicas', ['ASTO O ASO']],
            ['fr', 'FR', 'Pruebas serológicas', ['FACTOR REUMATOIDEO (FR)']],
            ['pcr', 'PCR', 'Pruebas serológicas', ['PCR CUALITATIVO (PROTEÍNA C REACTIVA)']],
            ['test_embarazo', 'Test de embarazo', 'Pruebas serológicas', ['TEST DE EMBARAZO EN SUERO (GONADOTROFINA CORIÓNICA HUMANA CUALITATIVO (HCG))', 'TEST DE EMBARAZO EN SUERO (GONADOTROFINA CORIÓNICA HUMANA CUALITATIVO)']],
            ['prueba_rapida_hepatitis_b', 'Prueba rápida Hepatitis B', 'Pruebas serológicas', ['PRUEBA RAPIDA PARA HEPATITIS B']],
            ['prueba_rapida_hepatitis_c', 'Prueba rápida Hepatitis C', 'Pruebas serológicas', ['PRUEBA RAPIDA PARA HEPATITIS C']],
            ['prueba_rapida_chagas', 'Prueba rápida Chagas', 'Pruebas serológicas', ['PRUEBA RAPIDA PARA CHAGAS']],
            ['prueba_rapida_vih', 'Prueba rápida VIH', 'Pruebas serológicas', ['PRUEBA RAPIDA PARA VIH']],
            ['prueba_rapida_sifilis', 'Prueba rápida Sífilis', 'Pruebas serológicas', ['PRUEBA RAPIDA PARA SIFILIS']],
            ['prueba_rapida_troponina', 'Prueba rápida Troponina', 'Pruebas serológicas', ['PRUEBA RAPIDA PARA TROPONINA']],
            ['rpr', 'RPR / VDRL', 'Pruebas serológicas', ['RPR- VDRL']],
            ['reaccion_widal_o', 'Reacción de Widal O', 'Pruebas serológicas', $widal],
            ['reaccion_widal_h', 'Reacción de Widal H', 'Pruebas serológicas', $widal],
            ['reaccion_widal_a', 'Reacción de Widal A', 'Pruebas serológicas', $widal],
            ['reaccion_widal_b', 'Reacción de Widal B', 'Pruebas serológicas', $widal],

            ['tipo_de_muestra', 'Tipo de muestra', 'Citoquímico', $citoquimico],
            ['citoquimico_cantidad', 'Cantidad (ml)', 'Citoquímico', $citoquimico],
            ['citoquimico_color', 'Color', 'Citoquímico', $citoquimico],
            ['citoquimico_aspecto', 'Aspecto', 'Citoquímico', $citoquimico],
            ['citoquimico_ph', 'pH', 'Citoquímico', $citoquimico],
            ['citoquimico_densidad', 'Densidad', 'Citoquímico', $citoquimico],
            ['citoquimico_glucosa', 'Glucosa (citoquímico)', 'Citoquímico', $citoquimico],
            ['citoquimico_proteinas_totales', 'Proteínas totales (citoquímico)', 'Citoquímico', $citoquimico],
            ['citoquimico_ldh', 'LDH (citoquímico)', 'Citoquímico', $citoquimico],
            ['citoquimico_globulos_blancos', 'Glóbulos blancos', 'Citoquímico', $citoquimico],
            ['citoquimico_polimorfonucleares', 'Polimorfonucleares (%)', 'Citoquímico', $citoquimico],
            ['citoquimico_mononucleares', 'Mononucleares (%)', 'Citoquímico', $citoquimico],
            ['citoquimico_observaciones', 'Observaciones del citoquímico', 'Citoquímico', $citoquimico],

            ['tolerancia_glucosa', 'Curva de tolerancia a la glucosa', 'Tolerancia a la glucosa', $ptg],

            ['gasometria_tipo', 'Tipo de gasometría', 'Gasometría', $gasometria],
            ['gasometria_muestra_estado', 'Estado de la muestra', 'Gasometría', $gasometria],
        ];

        $norm = fn ($v) => mb_strtolower(trim(preg_replace('/\s+/u', ' ', (string) $v)));

        $servicios = DB::table('servicios')->whereNull('deleted_at')->get(['id', 'nombre'])
            ->mapWithKeys(fn ($s) => [$norm($s->nombre) => $s->id]);

        $orden = 0;
        foreach ($datos as [$variable, $nombre, $seccion, $prestaciones]) {
            $orden += 10;
            $datoId = DB::table('datos_quimica_sanguinea')->insertGetId([
                'variable' => $variable,
                'nombre' => $nombre,
                'seccion' => $seccion,
                'orden' => $orden,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($prestaciones as $nombreServicio) {
                $servicioId = $servicios[$norm($nombreServicio)] ?? null;
                if ($servicioId) {
                    DB::table('datos_quimica_sanguinea_prestacion')->insertOrIgnore([
                        'dato_quimica_sanguinea_id' => $datoId,
                        'servicio_id' => $servicioId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    protected function seedPermiso(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permiso = Permission::firstOrCreate(['name' => 'Configuración química sanguínea', 'guard_name' => 'web']);

        DB::table('model_has_permissions')->insertOrIgnore([
            'permission_id' => $permiso->id,
            'model_type' => 'App\\Models\\User',
            'model_id' => 1,
        ]);
    }
};
