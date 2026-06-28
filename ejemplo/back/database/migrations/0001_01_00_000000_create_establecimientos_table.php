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
        Schema::create('establecimientos', function (Blueprint $table) {
            $table->id();

            // Nombre del establecimiento
            $table->string('nombre')->nullable();

            // Público / Privado
            $table->enum('tipo', ['PUBLICO', 'PRIVADO'])->nullable();

            // Nivel 1 o 2
            $table->string('nivel', 20)->nullable(); // Ej: "NIVEL I", "NIVEL II"

            // Dirección y contactos
            $table->string('direccion')->nullable();
            $table->string('inicial')->nullable();
            $table->string('telefono_contacto', 100)->nullable();

            // Responsable de laboratorio
            $table->string('responsable_laboratorio')->nullable();
            $table->string('telefono_responsable', 100)->nullable();

            // Estado (opcional)
            $table->string('estado', 20)->default('ACTIVO');

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('establecimientos');
    }
};
