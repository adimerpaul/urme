<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cuándo y quién anuló una venta o un gasto. Solo esquema: columnas nulas; las
 * anulaciones anteriores quedan en null (el reporte usa la auditoría para ellas).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dateTime('anulado_en')->nullable()->after('estado');
            $table->foreignId('anulado_por_id')->nullable()->after('anulado_en')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('anulado_por_id');
            $table->dropColumn('anulado_en');
        });
    }
};
