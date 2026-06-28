<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('parasitologias', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('solicitude_id');

            // SIMPLE o SERIADO
            $table->string('tipo')->default('SIMPLE'); // SIMPLE | SERIADO

            // MACROSCOPÍA
            $table->string('olor')->nullable();
            $table->string('color')->nullable();
            $table->string('consistencia')->nullable();
            $table->string('bacterias')->nullable();
            $table->text('otros')->nullable();

            // COPROPARASITOLÓGICO
            // SIMPLE: descripción_muestra
            $table->text('descripcion_muestra')->nullable();

            // SERIADO: descripción_muestra_1/2/3
            $table->text('descripcion_muestra_1')->nullable();
            $table->text('descripcion_muestra_2')->nullable();
            $table->text('descripcion_muestra_3')->nullable();

            $table->string('sangre_oculta')->nullable();          // NEGATIVO / POSITIVO / etc
            $table->string('prueba_rapida_rotavirus')->nullable(); // NEGATIVO / POSITIVO
            $table->string('moco_fecal')->nullable();              // (texto corto)
            $table->string('test_benedict')->nullable();           // NEGATIVO / POSITIVO
            $table->string('reaccion')->nullable();                // pH 7.0 neutro, etc
            $table->text('otros_examenes')->nullable();
//            otros_examenes_otros
            $table->text('otros_examenes_otros')->nullable();
            $table->string('code', 100)->nullable();
            $table->string('moco_fecal_positivo')->nullable();
            $table->text('otros_resultados')->nullable();

            $table->foreign('solicitude_id')->references('id')->on('solicitudes')->onDelete('cascade');

            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users');

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parasitologias');
    }
};
