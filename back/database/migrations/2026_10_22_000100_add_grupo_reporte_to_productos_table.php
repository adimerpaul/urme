<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrupación opcional en el informe de laboratorio: los productos de una
     * misma área con el mismo grupo_reporte salen bajo un solo título
     * (p. ej. ELECTROLITOS), ordenados por orden_reporte. Solo agrega columnas.
     */
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->string('grupo_reporte', 100)->nullable()->after('tipo_producto_id');
            $table->unsignedInteger('orden_reporte')->nullable()->after('grupo_reporte');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn(['grupo_reporte', 'orden_reporte']);
        });
    }
};
