<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('solicitudes_sap', function (Blueprint $table) {
            $table->string('apertura_programatica')->nullable()->after('unidad_solicitante');
            $table->string('nro_cite')->nullable()->after('apertura_programatica');
            $table->text('justificacion')->nullable()->after('observaciones');
        });

        Schema::table('solicitud_sap_detalles', function (Blueprint $table) {
            $table->foreignId('almacen_item_id')->nullable()->constrained('almacen_items')->nullOnDelete()->after('solicitud_sap_id');
            $table->string('imagen')->nullable()->after('almacen_item_id');
        });
    }

    public function down(): void
    {
        Schema::table('solicitudes_sap', function (Blueprint $table) {
            $table->dropColumn(['apertura_programatica', 'nro_cite', 'justificacion']);
        });

        Schema::table('solicitud_sap_detalles', function (Blueprint $table) {
            $table->dropForeign(['almacen_item_id']);
            $table->dropColumn(['almacen_item_id', 'imagen']);
        });
    }
};
