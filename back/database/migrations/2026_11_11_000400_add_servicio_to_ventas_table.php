<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Servicio de la venta (CURACIÓN, GINECOLOGÍA, PEDIATRÍA, ...): texto libre.
 * El formulario sugiere los servicios ya usados en ventas anteriores.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->string('servicio', 100)->nullable()->after('tipo_paciente');
            $table->index('servicio');
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropIndex(['servicio']);
            $table->dropColumn('servicio');
        });
    }
};
