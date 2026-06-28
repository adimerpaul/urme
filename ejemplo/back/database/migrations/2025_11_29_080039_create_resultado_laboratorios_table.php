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
        Schema::create('resultado_laboratorios', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('solicitude_id');   // la orden
            $table->unsignedBigInteger('area_rango_id')->nullable();
            $table->unsignedBigInteger('area_id');         // redundante pero útil para filtrar rápido
            // (Hematología, Química, etc.)

            // --- Para TODAS las áreas ---
            $table->double('valor_final')->nullable();     // lo que se mostrará al paciente
            $table->string('unidad')->nullable();
            $table->string('name')->nullable();
            $table->string('metodo_final')->nullable();    // ej: 'AUTOMATIZADO', 'MANUAL'

            // --- Para el caso especial de HEMATOLOGÍA (Área 1) ---
            $table->double('valor_automatizado')->nullable();
            $table->double('valor_manual')->nullable();
            $table->enum('preferido', ['AUTO', 'MAN'])
                ->nullable(); // cuál se toma como "valor_final"

            $table->text('observacion')->nullable();       // texto libre si el bioquímico quiere anotar algo

            $table->softDeletes();

            $table->foreign('solicitude_id')->references('id')->on('solicitudes');
            $table->foreign('area_rango_id')->references('id')->on('area_rangos');
            $table->timestamps();
        });
        Schema::create('perfiles_impresion', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');        // 'HEMOGRAMA', 'QUIMICA SANGUINEA'
            $table->string('codigo')->unique(); // 'HEMOGRAMA', 'QUIMICA'
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('perfil_impresion_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('perfil_id');
            $table->unsignedBigInteger('area_rango_id');

            $table->string('seccion')->nullable();  // 'RECUENTO DIFERENCIAL', 'INDICES HEMATIMETRICOS'
            $table->string('columna')->nullable();  // 'IZQ', 'DER'  (para separar izquierda/derecha)
            $table->integer('orden')->default(0);   // orden dentro de la sección

            $table->boolean('mostrar_en_paciente')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('perfil_id')->references('id')->on('perfiles_impresion')->onDelete('cascade');
            $table->foreign('area_rango_id')->references('id')->on('area_rangos')->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resultado_laboratorios');
        Schema::dropIfExists('perfil_impresion_items');
        Schema::dropIfExists('perfiles_impresion');
    }
};
