<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hematologias', function (Blueprint $table) {
            $table->id();

            // Relación 1 a 1 con la solicitud
            $table->unsignedBigInteger('solicitude_id')->unique();

            // ---------------------------
            // HEMOGRAMA BÁSICO
            // ---------------------------
            $table->decimal('globulos_rojos', 8, 2)->nullable();      // X10e12/L
            $table->decimal('globulos_blancos', 8, 2)->nullable();    // X10e9/L
            $table->decimal('plaquetas', 8, 2)->nullable();           // X10e9/L
            $table->decimal('hemoglobina', 8, 2)->nullable();         // g/L
            $table->decimal('hematocrito', 8, 2)->nullable();         // L/L

            // Índices hematimétricos
            $table->decimal('vcm', 8, 2)->nullable();                 // fL
            $table->decimal('hbcm', 8, 2)->nullable();                // pg
            $table->decimal('chcm', 8, 2)->nullable();                // g/L

            // Leucocitos totales (campo libre por si difiere de Glóbulos blancos)
            $table->decimal('leucocitos_totales', 8, 2)->nullable();  // X10e9/L

            // ---------------------------
            // RECUENTO DIFERENCIAL (% y absoluto)
            // ---------------------------
            $table->decimal('basofilos_porcentaje', 8, 2)->nullable();
            $table->decimal('basofilos_absoluto', 8, 2)->nullable();

            $table->decimal('eosinofilos_porcentaje', 8, 2)->nullable();
            $table->decimal('eosinofilos_absoluto', 8, 2)->nullable();

            $table->decimal('cayados_porcentaje', 8, 2)->nullable();
            $table->decimal('cayados_absoluto', 8, 2)->nullable();

            $table->decimal('segmentados_porcentaje', 8, 2)->nullable();
            $table->decimal('segmentados_absoluto', 8, 2)->nullable();

            $table->decimal('linfocitos_porcentaje', 8, 2)->nullable();
            $table->decimal('linfocitos_absoluto', 8, 2)->nullable();

            $table->decimal('monocitos_porcentaje', 8, 2)->nullable();
            $table->decimal('monocitos_absoluto', 8, 2)->nullable();

            $table->decimal('blastos_porcentaje', 8, 2)->nullable();
            $table->decimal('blastos_absoluto', 8, 2)->nullable();

            $table->decimal('metamielocitos_porcentaje', 8, 2)->nullable();
            $table->decimal('metamielocitos_absoluto', 8, 2)->nullable();

            $table->decimal('eritroblastos_porcentaje', 8, 2)->nullable();
            $table->decimal('eritroblastos_absoluto', 8, 2)->nullable();

            // Morfología de GR (texto libre)
            $table->text('morfologia_eritrocitos')->nullable();

            // ---------------------------
            // COAGULOGRAMA
            // ---------------------------
            $table->decimal('tiempo_protrombina', 8, 2)->nullable();      // Seg
            $table->decimal('actividad_protrombina', 8, 2)->nullable();   // %
            $table->decimal('inr', 8, 2)->nullable();
            $table->decimal('aptt', 8, 2)->nullable();                     // Seg
            $table->decimal('fibrinogeno', 8, 2)->nullable();              // g/L
            $table->decimal('ves', 8, 2)->nullable();                      // mm/hora
            $table->decimal('ipr', 8, 2)->nullable();                      // %
            $table->decimal('rc', 8, 2)->nullable();
            $table->decimal('ipr2', 8, 2)->nullable();                     // %

            // ---------------------------
            // GRUPO SANGUÍNEO
            // ---------------------------
            $table->string('grupo_sanguineo', 10)->nullable();             // O, A, B, AB
            $table->string('factor_rh', 10)->nullable();                   // Positivo, Negativo

            // Información general del análisis
            $table->string('metodo')->nullable();      // A, M, M/SA...
            $table->string('equipo', 100)->nullable();
            $table->string('equipo_otro', 100)->nullable();
            $table->string('code', 100)->nullable();

            $table->text('serie_roja')->nullable();
            $table->text('serie_blanca')->nullable();
            $table->text('serie_plaqueta')->nullable();

            $table->string('hemograma_metodo')->nullable();
            $table->string('hemograma_equipo')->nullable();
            $table->string('coagulograma_metodo')->nullable();
            $table->string('coagulograma_equipo')->nullable();

            $table->text('observaciones')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users');

            $table->foreign('solicitude_id')
                ->references('id')->on('solicitudes')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hematologias');
    }
};
