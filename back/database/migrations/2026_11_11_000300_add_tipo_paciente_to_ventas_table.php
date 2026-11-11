<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tipo de paciente de la venta: INTERNO, EXTERNO o AMBULATORIO.
 * Nullable para las ventas antiguas y los gastos de caja; el formulario
 * de venta lo exige y propone EXTERNO por defecto.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->string('tipo_paciente', 20)->nullable()->after('tipo_movimiento');
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn('tipo_paciente');
        });
    }
};
