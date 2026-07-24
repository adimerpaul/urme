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
        Schema::create('datos_hematologia', function (Blueprint $table) {
            $table->id();
            $table->string('variable')->unique();      // nombre del campo en hematologias (ej. globulos_rojos)
            $table->string('nombre');                  // etiqueta visible (ej. Glóbulos rojos)
            $table->string('seccion')->nullable();     // sección del formulario
            $table->integer('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('datos_hematologia_prestacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dato_hematologia_id')->constrained('datos_hematologia')->cascadeOnDelete();
            $table->foreignId('servicio_id')->constrained('servicios')->cascadeOnDelete();
            $table->unique(['dato_hematologia_id', 'servicio_id'], 'dato_hematologia_servicio_unique');
            $table->timestamps();
        });

        $this->seedDatos();
        $this->seedPermiso();
    }

    public function down(): void
    {
        Schema::dropIfExists('datos_hematologia_prestacion');
        Schema::dropIfExists('datos_hematologia');

        $permiso = DB::table('permissions')->where('name', 'Configuración hematología')->first();
        if ($permiso) {
            DB::table('model_has_permissions')->where('permission_id', $permiso->id)->delete();
            DB::table('permissions')->where('id', $permiso->id)->delete();
        }
    }

    protected function seedDatos(): void
    {
        // Mapeo tomado de los v-if por nombre de servicio que había en Hematologia.vue.
        // Sin prestaciones asignadas = la variable se muestra siempre.
        $coagulogramaTp = ['COAGULOGRAMA (TP,RECUENTO DE PLAQUETAS, APTT)', 'TIEMPO DE PROTROMBINA (TP)'];
        $indices = ['ÍNDICES HEMATIMÉTRICOS', 'HEMOGRAMA COMPLETO+ PLAQUETAS'];
        $hemograma = ['HEMOGRAMA COMPLETO+ PLAQUETAS'];
        $reticulocitos = ['RECUENTO DE RETICULOCITOS'];
        $frotis = ['FROTIS SANGUÍNEO/LEUCOGRAMA'];
        $grupo = ['GRUPO SANGUÍNEO Y FACTOR'];

        $datos = [
            ['globulos_rojos', 'Glóbulos rojos', 'Hemograma', []],
            ['globulos_blancos', 'Glóbulos blancos', 'Hemograma', []],
            ['plaquetas', 'Plaquetas', 'Hemograma', []],
            ['hemoglobina', 'Hemoglobina', 'Hemograma', []],
            ['hematocrito', 'Hematocrito', 'Hemograma', []],

            ['vcm', 'VCM', 'Índices hematimétricos', $indices],
            ['hbcm', 'HBCM', 'Índices hematimétricos', $indices],
            ['chcm', 'CHCM', 'Índices hematimétricos', $indices],

            ['basofilos_porcentaje', 'Basófilos', 'Recuento diferencial', $hemograma],
            ['eosinofilos_porcentaje', 'Eosinófilos', 'Recuento diferencial', $hemograma],
            ['cayados_porcentaje', 'Cayados', 'Recuento diferencial', $hemograma],
            ['segmentados_porcentaje', 'Segmentados', 'Recuento diferencial', $hemograma],
            ['linfocitos_porcentaje', 'Linfocitos', 'Recuento diferencial', $hemograma],
            ['monocitos_porcentaje', 'Monocitos', 'Recuento diferencial', $hemograma],
            ['blastos_porcentaje', 'Blastos', 'Recuento diferencial', $hemograma],
            ['metamielocitos_porcentaje', 'Metamielocitos', 'Recuento diferencial', $hemograma],
            ['eritroblastos_porcentaje', 'Eritroblastos', 'Recuento diferencial', $hemograma],

            ['tiempo_protrombina', 'Tiempo de protrombina (TP)', 'Coagulograma', $coagulogramaTp],
            ['actividad_protrombina', 'Actividad de protrombina', 'Coagulograma', $coagulogramaTp],
            ['inr', 'INR', 'Coagulograma', $coagulogramaTp],
            ['ves', 'V.S.G', 'Coagulograma', ['ERITROSEDIMENTACIÓN (VSG- VES)']],
            ['aptt', 'APTT', 'Coagulograma', ['COAGULOGRAMA (TP,RECUENTO DE PLAQUETAS, APTT)', 'TIEMPO PARCIAL DE TROMBOPLASTINA ACTIVADA (APTT)']],
            ['fibrinogeno', 'Fibrinógeno', 'Coagulograma', ['FIBRINÓGENO']],
            ['dimeros_d', 'Dímeros D', 'Coagulograma', ['FIBRINÓGENO']],

            ['ipr2', 'Reticulocitos', 'Otros', $reticulocitos],
            ['rc', 'IRC', 'Otros', $reticulocitos],
            ['ipr', 'IPR', 'Otros', $reticulocitos],

            ['serie_roja', 'Serie roja', 'Frotis de sangre periférica', ['FROTIS SANGUÍNEO/LEUCOGRAMA', 'MORFOLOGÍA DE GLÓBULOS ROJOS']],
            ['serie_blanca', 'Serie blanca', 'Frotis de sangre periférica', $frotis],
            ['serie_plaqueta', 'Serie plaquetaria', 'Frotis de sangre periférica', $frotis],

            ['grupo_sanguineo', 'Grupo sanguíneo', 'Grupo sanguíneo', $grupo],
            ['factor_rh', 'Factor Rh', 'Grupo sanguíneo', $grupo],
        ];

        $norm = fn ($v) => mb_strtolower(trim(preg_replace('/\s+/u', ' ', (string) $v)));

        $servicios = DB::table('servicios')->whereNull('deleted_at')->get(['id', 'nombre'])
            ->mapWithKeys(fn ($s) => [$norm($s->nombre) => $s->id]);

        $orden = 0;
        foreach ($datos as [$variable, $nombre, $seccion, $prestaciones]) {
            $orden += 10;
            $datoId = DB::table('datos_hematologia')->insertGetId([
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
                    DB::table('datos_hematologia_prestacion')->insertOrIgnore([
                        'dato_hematologia_id' => $datoId,
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

        $permiso = Permission::firstOrCreate(['name' => 'Configuración hematología', 'guard_name' => 'web']);

        DB::table('model_has_permissions')->insertOrIgnore([
            'permission_id' => $permiso->id,
            'model_type' => 'App\\Models\\User',
            'model_id' => 1,
        ]);
    }
};
