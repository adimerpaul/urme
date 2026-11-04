<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Categoría (tipo_producto_padres) y subcategoría (tipo_productos) de los gastos
 * de Caja Administrativa y Caja General. Solo esquema: las columnas son nullable,
 * los movimientos existentes quedan sin categoría y la columna de texto
 * `categoria` no se toca (se conserva como dato histórico).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('caja_movimientos', function (Blueprint $table) {
            $table->unsignedBigInteger('tipo_producto_padre_id')->nullable()->after('categoria');
            $table->unsignedBigInteger('tipo_producto_id')->nullable()->after('tipo_producto_padre_id');
            $table->index('tipo_producto_padre_id', 'caja_movimientos_tipo_producto_padre_id_index');
            $table->index('tipo_producto_id', 'caja_movimientos_tipo_producto_id_index');
            $table->foreign('tipo_producto_padre_id', 'caja_movimientos_tipo_producto_padre_id_foreign')
                ->references('id')->on('tipo_producto_padres')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('tipo_producto_id', 'caja_movimientos_tipo_producto_id_foreign')
                ->references('id')->on('tipo_productos')->cascadeOnUpdate()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('caja_movimientos', function (Blueprint $table) {
            $table->dropForeign('caja_movimientos_tipo_producto_padre_id_foreign');
            $table->dropForeign('caja_movimientos_tipo_producto_id_foreign');
            $table->dropIndex('caja_movimientos_tipo_producto_padre_id_index');
            $table->dropIndex('caja_movimientos_tipo_producto_id_index');
            $table->dropColumn(['tipo_producto_padre_id', 'tipo_producto_id']);
        });
    }
};
