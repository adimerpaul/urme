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
        Schema::create('papiloma_humanos', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('solicitude_id');

            // Resultados PCR
            $table->string('hpv_alto_riesgo')->nullable(); // NO DETECTADO / DETECTADO

            $table->string('hpv_16')->nullable();
            $table->string('hpv_18')->nullable();
            $table->string('hpv_45')->nullable();
            $table->string('hpv_26')->nullable();
            $table->string('hpv_31')->nullable();
            $table->string('hpv_33')->nullable();
            $table->string('hpv_35')->nullable();
            $table->string('hpv_39')->nullable();
            $table->string('hpv_51')->nullable();
            $table->string('hpv_52')->nullable();
            $table->string('hpv_53')->nullable();
            $table->string('hpv_56')->nullable();
            $table->string('hpv_58')->nullable();
            $table->string('hpv_59')->nullable();
            $table->string('hpv_66')->nullable();
            $table->string('hpv_67')->nullable();
            $table->string('hpv_68')->nullable();
            $table->string('hpv_69')->nullable();
            $table->string('hpv_70')->nullable();
            $table->string('hpv_73')->nullable();
            $table->string('hpv_82')->nullable();
            $table->string('hpv_97')->nullable();
            $table->string('code', 100)->nullable();

            $table->text('observaciones')->nullable();

            $table->string('metodo')->nullable();
            $table->string('equipo')->nullable();
            $table->string('numeracion')->nullable();
            $table->string('codigo_muestra')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->foreign('solicitude_id')
                ->references('id')
                ->on('solicitudes')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('papiloma_humanos');
    }
};
