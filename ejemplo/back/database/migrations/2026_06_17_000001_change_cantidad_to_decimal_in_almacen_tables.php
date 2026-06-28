<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compra_detalles', function (Blueprint $table) {
            $table->decimal('cantidad', 10, 4)->nullable()->change();
            $table->decimal('cantidad_venta', 10, 4)->default(0)->change();
        });

        Schema::table('detalle_pedidos', function (Blueprint $table) {
            $table->decimal('cantidad', 10, 4)->nullable()->change();
        });

        Schema::table('despacho_detalles', function (Blueprint $table) {
            $table->decimal('cantidad', 10, 4)->change();
        });

        Schema::table('despacho_detalle_reales', function (Blueprint $table) {
            $table->decimal('cantidad', 10, 4)->change();
        });
    }

    public function down(): void
    {
        Schema::table('compra_detalles', function (Blueprint $table) {
            $table->integer('cantidad')->nullable()->change();
            $table->integer('cantidad_venta')->default(0)->change();
        });

        Schema::table('detalle_pedidos', function (Blueprint $table) {
            $table->integer('cantidad')->nullable()->change();
        });

        Schema::table('despacho_detalles', function (Blueprint $table) {
            $table->integer('cantidad')->change();
        });

        Schema::table('despacho_detalle_reales', function (Blueprint $table) {
            $table->integer('cantidad')->change();
        });
    }
};
