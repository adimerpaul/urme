<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Arqueo de efectivo del cierre (fórmula nueva, ver docs/ANALISIS_CIERRES_CAJA.md §8).
 * Solo esquema: columnas nulas. Los cierres existentes quedan con formula = null
 * y no se modifican; el reporte los calcula al vuelo sin guardar nada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cierres_caja', function (Blueprint $table) {
            // 'EFECTIVO' = cierre calculado con la fórmula de solo efectivo; null = cierre anterior.
            $table->string('formula', 10)->nullable()->after('diferencia');
            $table->decimal('ingresos_total', 14, 2)->nullable()->after('formula');
            $table->decimal('efectivo_ingresos', 14, 2)->nullable()->after('ingresos_total');
            $table->decimal('qr_total', 14, 2)->nullable()->after('efectivo_ingresos');
            $table->unsignedInteger('qr_cantidad')->nullable()->after('qr_total');
            $table->decimal('otros_total', 14, 2)->nullable()->after('qr_cantidad');
            $table->unsignedInteger('otros_cantidad')->nullable()->after('otros_total');
            $table->decimal('gastos_efectivo', 14, 2)->nullable()->after('otros_cantidad');
            $table->unsignedInteger('gastos_cantidad')->nullable()->after('gastos_efectivo');
            $table->decimal('efectivo_esperado', 14, 2)->nullable()->after('gastos_cantidad');
            $table->decimal('diferencia_efectivo', 14, 2)->nullable()->after('efectivo_esperado');
        });
    }

    public function down(): void
    {
        Schema::table('cierres_caja', function (Blueprint $table) {
            $table->dropColumn([
                'formula', 'ingresos_total', 'efectivo_ingresos', 'qr_total', 'qr_cantidad', 'otros_total',
                'otros_cantidad', 'gastos_efectivo', 'gastos_cantidad', 'efectivo_esperado', 'diferencia_efectivo',
            ]);
        });
    }
};
