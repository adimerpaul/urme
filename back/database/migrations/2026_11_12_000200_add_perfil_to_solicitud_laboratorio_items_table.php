<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * De qué perfil de la solicitud vino cada examen. NULL = examen suelto, que es
 * lo que quedan todos los ítems existentes. Solo esquema.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solicitud_laboratorio_items', function (Blueprint $table) {
            $table->unsignedBigInteger('solicitud_laboratorio_perfil_id')->nullable()->after('producto_id');
            $table->index('solicitud_laboratorio_perfil_id', 'sli_perfil_id_index');
            $table->foreign('solicitud_laboratorio_perfil_id', 'sli_perfil_id_foreign')
                ->references('id')->on('solicitud_laboratorio_perfiles')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('solicitud_laboratorio_items', function (Blueprint $table) {
            $table->dropForeign('sli_perfil_id_foreign');
            $table->dropIndex('sli_perfil_id_index');
            $table->dropColumn('solicitud_laboratorio_perfil_id');
        });
    }
};
