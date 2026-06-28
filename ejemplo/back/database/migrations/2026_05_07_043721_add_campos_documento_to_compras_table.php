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
        Schema::table('compras', function (Blueprint $table) {
            $table->string('categoria_programatica')->nullable()->after('nro_factura');
            $table->string('orden_de_compra')->nullable()->after('categoria_programatica');
            $table->string('codigo_interno')->nullable()->after('orden_de_compra');
        });
    }

    public function down(): void
    {
        Schema::table('compras', function (Blueprint $table) {
            $table->dropColumn(['categoria_programatica', 'orden_de_compra', 'codigo_interno']);
        });
    }
};
