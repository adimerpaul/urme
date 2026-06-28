<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cultivo_antibiogramas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('solicitude_id');

            // Datos “microbiología” (los que salen en el papel)
            $table->string('numero_identificacion')->nullable();   // SUS
            $table->string('codigo_microbiologia')->nullable();
            $table->string('institucion')->nullable();
            $table->string('code', 100)->nullable();

            $table->string('cultivo_solicitado')->nullable();      // Urocultivo / Coprocultivo / Esputo / etc.
            $table->string('localizacion')->nullable();            // Interno/Externo
            $table->string('servicio')->nullable();
            $table->string('sala')->nullable();
            $table->string('cama')->nullable();

            $table->date('fecha_ingreso')->nullable();
            $table->date('fecha_salida')->nullable();

            // Secciones
            $table->string('tincion_gram')->nullable();            // “Bacilos Gram(-) Abundante”, etc
            $table->string('conteo_colonia')->nullable();          // “> 100.000 UFC/ml”
            $table->string('microorganismo')->nullable();          // “Escherichia coli”
            $table->string('mecanismo_resistencia')->nullable();   // “Positivo”, etc (opcional)

            $table->text('observaciones')->nullable();

            // Lista dinámica de antibióticos con estado (S/R/I)
            $table->json('antibiograma')->nullable(); // [{antibiotico:"Amikacina", estado:"S"}...]

            $table->softDeletes();
            $table->timestamps();

            $table->foreign('solicitude_id')->references('id')->on('solicitudes')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cultivo_antibiogramas');
    }
};
