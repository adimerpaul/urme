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
        Schema::create('agrupaciones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');              // etiqueta del checkbox en el formulario de solicitud
            $table->integer('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('agrupacion_prestacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agrupacion_id')->constrained('agrupaciones')->cascadeOnDelete();
            $table->foreignId('servicio_id')->constrained('servicios')->cascadeOnDelete();
            $table->unique(['agrupacion_id', 'servicio_id'], 'agrupacion_servicio_unique');
            $table->timestamps();
        });

        $this->seedAgrupaciones();
        $this->seedPermiso();
    }

    public function down(): void
    {
        Schema::dropIfExists('agrupacion_prestacion');
        Schema::dropIfExists('agrupaciones');

        $permiso = DB::table('permissions')->where('name', 'Configuración agrupaciones')->first();
        if ($permiso) {
            DB::table('model_has_permissions')->where('permission_id', $permiso->id)->delete();
            DB::table('permissions')->where('id', $permiso->id)->delete();
        }
    }

    protected function seedAgrupaciones(): void
    {
        // Grupos rápidos que estaban hardcodeados en SolicitudForm.vue
        // (nombre del checkbox => codigos de servicios que selecciona).
        $agrupaciones = [
            ['Bilirrubinas totales y fracciones', [17]],
            ['Inmunoglobulinas IgG, IgM, IgA', [144, 145, 146]],
            ['Coproparasitológico simple', [70]],
            ['Moco fecal', [75]],
            ['Coproparasitológico seriado', [71]],
            ['Nitrógeno ureico sérico y urea', [39, 51]],
            ['Sangre Oculta en Heces', [77]],
            ['Proteína C Reactiva (PCR)', [54]],
            ['Creatinina sérica', [25]],
            ['Proteinuria de 24 horas', [45]],
            ['Cultivo p/ gérmenes comunes y antibiograma', [79]],
            ['Prueba rápida para sífilis', [59]],
            ['Examen general de orina', [65]],
            ['Coagulograma', [10, 12, 13, 1]],
            ['Electrolitos (sodio, potasio, cloro)', [26]],
            ['Tiempo de protrombina/APTT', [12]],
            ['Factor reumatoideo', [53]],
            ['Transaminasas TGO – TGP', [49, 48]],
            ['Fosfatasa alcalina y ácida', [27]],
            ['Test de embarazo en sangre (HCG)', [63]],
            ['Frotis tinción Gram', [82]],
            ['Reactantes de fase aguda (VES, Fibrinógeno, PCR)', [2, 3, 54]],
            ['Grupo sanguíneo y factor Rh', [5]],
            ['Reacción Widal', [61]],
            ['Glicemia', [31]],
            ['RPR para sífilis – VDRL', [62]],
            ['Gasometría arterial o venosa', [30]],
            ['Tamizaje Neonatal', [17]],
            ['Hemograma completo', [6]],
            ['VIH', [58]],
            ['Perfil Tiroideo', [116, 118, 119, 120, 121]],
            ['Ionograma', [35]],
        ];

        $servicios = DB::table('servicios')->whereNull('deleted_at')->whereNotNull('codigo')
            ->pluck('id', 'codigo');

        $orden = 0;
        foreach ($agrupaciones as [$nombre, $codigos]) {
            $orden += 10;
            $agrupacionId = DB::table('agrupaciones')->insertGetId([
                'nombre' => $nombre,
                'orden' => $orden,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($codigos as $codigo) {
                $servicioId = $servicios[$codigo] ?? null;
                if ($servicioId) {
                    DB::table('agrupacion_prestacion')->insertOrIgnore([
                        'agrupacion_id' => $agrupacionId,
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

        $permiso = Permission::firstOrCreate(['name' => 'Configuración agrupaciones', 'guard_name' => 'web']);

        DB::table('model_has_permissions')->insertOrIgnore([
            'permission_id' => $permiso->id,
            'model_type' => 'App\\Models\\User',
            'model_id' => 1,
        ]);
    }
};
