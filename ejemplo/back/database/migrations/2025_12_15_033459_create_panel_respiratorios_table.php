<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('panel_respiratorios', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('solicitude_id')->unique();

            // Resultados (NO DETECTADO / DETECTADO)
            $table->string('vrs_ab')->nullable();                 // Virus Sincitial Respiratorio A,B
            $table->string('influenza_b')->nullable();
            $table->string('influenza_a')->nullable();
            $table->string('sars_cov_2')->nullable();
            $table->string('streptococcus_pyogenes')->nullable();
            $table->string('adenovirus')->nullable();
            $table->string('rhinovirus')->nullable();
            $table->string('coronavirus_229e_oc43')->nullable();
            $table->string('parainfluenza_1_2')->nullable();
            $table->string('coronavirus_nl63_hku1')->nullable();
            $table->string('parainfluenza_3_4')->nullable();
            $table->string('haemophilus_influenzae')->nullable();
            $table->string('bordetella_pertussis')->nullable();
            $table->string('streptococcus_pneumoniae')->nullable();
            $table->string('bocavirus')->nullable();
            $table->string('mycoplasma_pneumoniae')->nullable();
            $table->string('metapneumovirus')->nullable();
            $table->string('enterovirus')->nullable();
            $table->string('legionella_pneumophila')->nullable();

            $table->text('observaciones')->nullable();
            $table->string('code', 100)->nullable();

            $table->string('metodo')->nullable();
            $table->string('equipo')->nullable();
            $table->string('numeracion')->nullable();
            $table->string('codigo_muestra')->nullable();

            $table->foreign('solicitude_id')->references('id')->on('solicitudes')->onDelete('cascade');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('panel_respiratorios');
    }
};
