<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marca de perfil de exámenes de laboratorio (docs/ANALISIS_PERFILES_FASE0.md).
 * Solo esquema: los productos existentes quedan en false.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->boolean('es_perfil')->default(false)->after('precio_seguro');
            $table->index('es_perfil', 'productos_es_perfil_index');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropIndex('productos_es_perfil_index');
            $table->dropColumn('es_perfil');
        });
    }
};
