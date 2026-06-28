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
        Schema::create('pacientes', function (Blueprint $table) {
            $table->id();
            $table->date('fecha_recepcion')->nullable();
            $table->time('hora_recepcion')->nullable();

            $table->string('nombre_completo');
            $table->date('fecha_nac')->nullable();
            $table->enum('genero', ['F','M','OTRO'])->nullable();
            $table->tinyInteger('edad')->unsigned()->nullable();

            $table->string('ci', 30)->nullable();
            $table->string('telefono', 40)->nullable();
            $table->string('direccion', 200)->nullable();

            $table->boolean('discapacidad')->default(false);
            $table->string('discapacidad_cual', 120)->nullable();
            $table->string('discapacidad_otro', 120)->nullable();

            $table->boolean('embarazo')->default(false);
            $table->date('fum')->nullable();
            $table->tinyInteger('sem_gest')->unsigned()->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pacientes');
    }
};
