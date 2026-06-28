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
        Schema::create('solicitude_propiedades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitude_id')
                ->constrained('solicitudes')
                ->onDelete('cascade');

            $table->foreignId('area_id')
                ->constrained('areas')
                ->onDelete('cascade');

            // Nombre del campo (ej: "aceptada", "coagulo", "equipo", "sala", etc.)
            $table->string('campo');

            // Valor en texto (SI/NO, texto libre, etc.)
            $table->text('valor')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitude_propiedades');
    }
};
