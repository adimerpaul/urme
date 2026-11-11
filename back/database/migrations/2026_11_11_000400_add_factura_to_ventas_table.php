<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Factura de la venta: si se marcó "Con factura" al vender, se guarda su número.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->boolean('con_factura')->default(false)->after('comentario');
            $table->string('numero_factura', 50)->nullable()->after('con_factura');
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn(['con_factura', 'numero_factura']);
        });
    }
};
