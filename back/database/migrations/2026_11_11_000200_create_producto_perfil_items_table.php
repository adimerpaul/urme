<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Exámenes que componen cada perfil. Quitar un examen borra la fila (sin
 * soft delete); el historial queda en la auditoría del modelo ProductoPerfilItem.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('producto_perfil_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('perfil_id');
            $table->unsignedBigInteger('producto_id');
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();

            $table->unique(['perfil_id', 'producto_id'], 'producto_perfil_items_perfil_producto_unique');
            $table->index('producto_id', 'producto_perfil_items_producto_id_index');
            $table->foreign('perfil_id', 'producto_perfil_items_perfil_id_foreign')
                ->references('id')->on('productos')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('producto_id', 'producto_perfil_items_producto_id_foreign')
                ->references('id')->on('productos')->cascadeOnUpdate()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producto_perfil_items');
    }
};
