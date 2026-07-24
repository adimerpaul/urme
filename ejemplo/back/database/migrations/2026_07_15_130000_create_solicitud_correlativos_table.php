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
        Schema::create('solicitud_correlativos', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_atencion', 10)->default('SI');   // SI = interno, NO = externo
            $table->unsignedSmallInteger('anio');
            $table->unsignedTinyInteger('mes');
            $table->unsignedInteger('ultimo_numero')->default(0); // último código usado en el mes
            $table->timestamps();
            $table->unique(['tipo_atencion', 'anio', 'mes'], 'solicitud_correlativo_unique');
        });

        $this->seedPermiso();
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitud_correlativos');

        $permiso = DB::table('permissions')->where('name', 'Generar código solicitud')->first();
        if ($permiso) {
            DB::table('model_has_permissions')->where('permission_id', $permiso->id)->delete();
            DB::table('permissions')->where('id', $permiso->id)->delete();
        }
    }

    protected function seedPermiso(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permiso = Permission::firstOrCreate(['name' => 'Generar código solicitud', 'guard_name' => 'web']);

        DB::table('model_has_permissions')->insertOrIgnore([
            'permission_id' => $permiso->id,
            'model_type' => 'App\\Models\\User',
            'model_id' => 1,
        ]);
    }
};
