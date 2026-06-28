<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quimica_sanguineas', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('solicitude_id');
            $table->foreign('solicitude_id')
                ->references('id')
                ->on('solicitudes')
                ->onDelete('cascade');

            // PRINCIPALES ANALITOS (numéricos)
            $table->decimal('acido_urico', 8, 2)->nullable();
            $table->decimal('albumina', 8, 2)->nullable();
            $table->decimal('proteinas_totales', 8, 2)->nullable();

            $table->decimal('bilirrubina_total', 8, 2)->nullable();
            $table->decimal('bilirrubina_directa', 8, 2)->nullable();
            $table->decimal('bilirrubina_indirecta', 8, 2)->nullable();

            $table->decimal('got', 8, 2)->nullable();           // G.O.T. (TGO)
            $table->decimal('gpt', 8, 2)->nullable();           // G.P.T. (TGP)
            $table->decimal('fosfatasa_alcalina', 8, 2)->nullable();
            $table->decimal('ggt', 8, 2)->nullable();
            $table->decimal('amilasa', 8, 2)->nullable();

            $table->decimal('glucosa', 8, 2)->nullable();
            $table->decimal('urea', 8, 2)->nullable();
            $table->decimal('nus', 8, 2)->nullable();           // Nitrógeno ureico sérico
            $table->decimal('creatinina', 8, 2)->nullable();

            $table->decimal('trigliceridos', 8, 2)->nullable();
            $table->decimal('colesterol_total', 8, 2)->nullable();
            $table->decimal('hdl_colesterol', 8, 2)->nullable();
            $table->decimal('ldl_colesterol', 8, 2)->nullable();
            $table->decimal('vldl_colesterol', 8, 2)->nullable();

            $table->decimal('ck_total', 8, 2)->nullable();
            $table->decimal('ck_mb', 8, 2)->nullable();

            $table->decimal('ferritina', 8, 2)->nullable()->comment('Si deseas manejar numérico');
            $table->decimal('hierro_serico', 8, 2)->nullable();

            $table->decimal('got_cinetico', 8, 2)->nullable();
            $table->decimal('gpt_cinetico', 8, 2)->nullable();

            $table->decimal('hb_glicosilada', 8, 2)->nullable(); // Hb glicosilada %
            $table->decimal('hb_a1c', 8, 2)->nullable();

            // ELECTROLITOS Y MINERALES
            $table->decimal('sodio', 8, 2)->nullable();
            $table->decimal('potasio', 8, 2)->nullable();
            $table->decimal('cloro', 8, 2)->nullable();
            $table->decimal('calcio', 8, 2)->nullable();
            $table->decimal('fosforo', 8, 2)->nullable();
            $table->decimal('magnesio', 8, 2)->nullable();

            $table->decimal('ldh', 8, 2)->nullable();
            $table->decimal('lipasa', 8, 2)->nullable();

            // ORINA 24 HRS
            $table->decimal('creatinuria_24h', 8, 2)->nullable();
            $table->decimal('proteinuria_24h', 8, 2)->nullable();
            $table->decimal('volumen_24h', 8, 2)->nullable();

            // SEROLÓGICOS (algunos son cualitativos)
            $table->decimal('aso', 8, 2)->nullable();
            $table->decimal('fr', 8, 2)->nullable();
            $table->decimal('pcr', 8, 2)->nullable();

            $table->string('prueba_rapida_vih', 50)->nullable();    // No reactivo / Reactivo
            $table->string('rpr', 50)->nullable();                  // No reactivo / Reactivo
            $table->string('reaccion_widal', 100)->nullable();      // O-, H-, A-, B-
            $table->string('dce', 100)->nullable();                 // D.C.E.

            // Campos extra
            $table->text('observaciones')->nullable();
            $table->string('metodo', 100)->nullable();
            $table->string('equipo', 150)->nullable();
            $table->string('equipo_otro', 150)->nullable();
            $table->string('code', 100)->nullable();
//            Globulina = Proteinas_Totales – Albumina
//Relacion A/G= Albumina/Globulina
            $table->decimal('globulina', 8, 2)->nullable();
            $table->decimal('relacion_ag', 8, 2)->nullable();


            $table->decimal('citoquimico_cantidad', 8, 2)->nullable();
            $table->string('tipo_de_muestra', 255)->nullable();
            $table->string('test_embarazo', 50)->nullable();    // No reactivo / Reactivo
            $table->string('citoquimico_color', 50)->nullable();
            $table->string('citoquimico_aspecto', 50)->nullable();
            $table->decimal('citoquimico_glucosa', 8, 2)->nullable();
            $table->string('citoquimico_ph', 8, 2)->nullable();
            $table->decimal('citoquimico_proteinas_totales', 8, 2)->nullable();
            $table->decimal('citoquimico_albumina', 8, 2)->nullable();
            $table->decimal('citoquimico_ldh', 8, 2)->nullable();
            $table->decimal('citoquimico_globulos_blancos', 8, 2)->nullable();
            $table->decimal('citoquimico_polimorfonucleares', 8, 2)->nullable();
            $table->decimal('citoquimico_mononucleares', 8, 2)->nullable();
            $table->string('citoquimico_densidad', 8, 2)->nullable();

//            tolerancia de glucosa del 1 al 6
            $table->decimal('tolerancia_glucosa_1h', 8, 2)->nullable();
            $table->decimal('tolerancia_glucosa_2h', 8, 2)->nullable();
            $table->decimal('tolerancia_glucosa_3h', 8, 2)->nullable();
            $table->decimal('tolerancia_glucosa_4h', 8, 2)->nullable();
            $table->decimal('tolerancia_glucosa_5h', 8, 2)->nullable();
            //            tolerancia de glucosa del 1 al 6 horas
            $table->string('tolerancia_hora_1h', 8, 2)->nullable();
            $table->string('tolerancia_hora_2h', 8, 2)->nullable();
            $table->string('tolerancia_hora_3h', 8, 2)->nullable();
            $table->string('tolerancia_hora_4h', 8, 2)->nullable();
            $table->string('tolerancia_hora_5h', 8, 2)->nullable();

            $table->string('prueba_rapida_hepatitis_b', 50)->nullable();    // No reactivo / Reactivo
            $table->string('prueba_rapida_hepatitis_c', 50)->nullable();    // No reactivo / Reactivo
            $table->string('prueba_rapida_chagas', 50)->nullable();         // No reactivo / Reactivo
            $table->string('prueba_rapida_sifilis', 50)->nullable();         // No reactivo / Reactivo
            $table->string('prueba_rapida_troponina', 50)->nullable();         // No reactivo / Reactivo

            $table->string('reaccion_widal_o', 50)->nullable();
            $table->string('reaccion_widal_h', 50)->nullable();
            $table->string('reaccion_widal_a', 50)->nullable();
            $table->string('reaccion_widal_b', 50)->nullable();

            $table->string('trf')->nullable();

            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users');


            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quimica_sanguineas');
    }
};
