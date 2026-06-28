<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('panel_sexuales', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('solicitude_id')->unique();

            $table->string('chlamydia_trachomatis')->nullable();
            $table->string('mycoplasma_genitalium')->nullable();
            $table->string('neisseria_gonorrhoeae')->nullable();
            $table->string('trichomonas_vaginalis')->nullable();
            $table->string('ureaplasma_urealyticum')->nullable();
            $table->string('ureaplasma_parvum')->nullable();
            $table->string('mycoplasma_hominis')->nullable();
            $table->string('hsv_1')->nullable();
            $table->string('hsv_2')->nullable();
            $table->string('treponema_pallidum')->nullable();
            $table->string('candida_albicans')->nullable();
            $table->string('gardnerella_vaginalis')->nullable();

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
        Schema::dropIfExists('panel_sexuales');
    }
};
