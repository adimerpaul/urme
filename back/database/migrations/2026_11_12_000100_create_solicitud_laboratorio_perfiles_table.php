<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Perfiles de exámenes tal como quedaron en cada solicitud (nombre y precio
 * copiados al guardar). Solo esquema: la tabla nace vacía.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitud_laboratorio_perfiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('solicitude_id');
            $table->unsignedBigInteger('perfil_producto_id')->nullable();
            $table->string('nombre');
            $table->decimal('precio', 10, 2)->default(0);
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('solicitude_id', 'slp_solicitude_id_index');
            $table->index('perfil_producto_id', 'slp_perfil_producto_id_index');
            $table->foreign('solicitude_id', 'slp_solicitude_id_foreign')
                ->references('id')->on('solicitudes')->cascadeOnDelete();
            $table->foreign('perfil_producto_id', 'slp_perfil_producto_id_foreign')
                ->references('id')->on('productos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitud_laboratorio_perfiles');
    }
};
