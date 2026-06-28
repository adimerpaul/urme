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
        Schema::create('consentimientos', function (Blueprint $table) {
            $table->id();

            // Relación opcional con paciente
            $table->foreignId('paciente_id')
                ->nullable()
                ->constrained('pacientes');

            // Encabezado del formulario
            $table->date('fecha_recepcion')->nullable();
            $table->string('hora_recepcion', 20)->nullable();
            $table->date('fecha_solicitud')->nullable();

            // Datos del paciente (copiados para dejar constancia tal como firmó)
            $table->string('nombre_completo')->nullable();
            $table->date('fecha_nac')->nullable();
            $table->string('genero', 10)->nullable();
            $table->unsignedInteger('edad')->nullable();
            $table->string('ci', 50)->nullable();
            $table->string('telefono', 100)->nullable();
            $table->string('direccion', 255)->nullable();

            // Discapacidad
            $table->boolean('discapacidad')->nullable();
            $table->string('discapacidad_cual')->nullable();

            // Embarazo
            $table->boolean('embarazo')->nullable();
            $table->date('fum')->nullable();
            $table->unsignedInteger('sem_gest')->nullable();

            // Medicamento / tratamiento
            $table->boolean('medicamento')->nullable();
            $table->string('tratamiento')->nullable();

            // Condición (checkboxes del formulario)
            $table->string('condicion')->nullable(); // BASAL, AYUNO_PROL, POST_PRANDIAL, ETAPA_GESTACION
            $table->string('etapa_gestacion')->nullable();

            // Parte de consentimiento / rechazo
//            $table->enum('tipo', ['ACEPTA', 'RECHAZA']);  // ACEPTA o RECHAZA
            $table->string('tipo')->nullable();  // ACEPTA o RECHAZA
            $table->string('declarante_nombre')->nullable();     // "Yo: ..."
            $table->string('declarante_condicion')->nullable();
            $table->string('declarante_condicion_otro')->nullable();
            $table->date('fecha_consentimiento')->nullable();    // para control por fecha

            // Usuario que registró el consentimiento
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users');

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consentimientos');
    }
};
