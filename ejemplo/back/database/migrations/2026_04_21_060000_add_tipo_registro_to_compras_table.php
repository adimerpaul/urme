<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compras', function (Blueprint $table) {
            $table->string('tipo_registro', 30)->default('ENTRADA')->after('fecha_hora');
            $table->string('motivo_registro', 50)->default('COMPRA')->after('tipo_registro');
            $table->index(['tipo_registro', 'fecha_hora']);
        });
    }

    public function down(): void
    {
        Schema::table('compras', function (Blueprint $table) {
            $table->dropIndex(['tipo_registro', 'fecha_hora']);
            $table->dropColumn(['tipo_registro', 'motivo_registro']);
        });
    }
};
