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
            $table->string('metodo_orden')->nullable()->after('orden_de_compra');
            $table->date('fecha_orden')->nullable()->after('metodo_orden');
        });
    }

    public function down(): void
    {
        Schema::table('compras', function (Blueprint $table) {
            $table->dropColumn(['metodo_orden', 'fecha_orden']);
        });
    }
};
