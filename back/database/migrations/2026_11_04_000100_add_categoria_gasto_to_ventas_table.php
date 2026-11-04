<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Categoría (tipo_producto_padres) y subcategoría (tipo_productos) de los gastos
 * de caja que se registran en ventas (tipo_movimiento EGRESO). Solo esquema: las
 * columnas son nullable y las ventas existentes quedan sin categoría.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->unsignedBigInteger('tipo_producto_padre_id')->nullable()->after('tipo_movimiento');
            $table->unsignedBigInteger('tipo_producto_id')->nullable()->after('tipo_producto_padre_id');
            $table->index('tipo_producto_padre_id', 'ventas_tipo_producto_padre_id_index');
            $table->index('tipo_producto_id', 'ventas_tipo_producto_id_index');
            $table->foreign('tipo_producto_padre_id', 'ventas_tipo_producto_padre_id_foreign')
                ->references('id')->on('tipo_producto_padres')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('tipo_producto_id', 'ventas_tipo_producto_id_foreign')
                ->references('id')->on('tipo_productos')->cascadeOnUpdate()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropForeign('ventas_tipo_producto_padre_id_foreign');
            $table->dropForeign('ventas_tipo_producto_id_foreign');
            $table->dropIndex('ventas_tipo_producto_padre_id_index');
            $table->dropIndex('ventas_tipo_producto_id_index');
            $table->dropColumn(['tipo_producto_padre_id', 'tipo_producto_id']);
        });
    }
};
