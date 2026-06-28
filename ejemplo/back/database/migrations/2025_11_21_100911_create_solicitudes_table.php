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
        Schema::create('solicitudes', function (Blueprint $table) {
            $table->id();

            // Relaciones opcionales
            $table->foreignId('paciente_id')
                ->nullable()
                ->constrained('pacientes');

            $table->foreignId('doctor_id')
                ->nullable()
                ->constrained('doctors');

            // Cabecera
            $table->string('codigo_solicitud')->nullable();
            $table->string('iniciales')->nullable();
            $table->string('tipo_atencion')->nullable();
            $table->string('tipo_otro')->nullable();
            $table->date('fecha_solicitud')->nullable();
            $table->time('hora_solicitud')->nullable();
            $table->string('establecimiento_salud')->nullable();
            $table->string('zona_establecimiento')->nullable();
            $table->text('diagnostico_clinico')->nullable();
            $table->string('diagnostico_select')->nullable();
            $table->string('estado')->default('CREADO');
            $table->integer('codigo')->nullable();
            $table->string('nro_registro')->nullable();
            $table->dateTime('fecha_creacion')->nullable();
            $table->dateTime('fecha_pre_analitica')->nullable();
            $table->dateTime('fecha_envio_analitica')->nullable();
            $table->dateTime('fecha_aceptacion_analitica')->nullable();
            $table->dateTime('fecha_finalizacion')->nullable();
            $table->string('sala')->nullable();
            $table->string('cama')->nullable();
            $table->string('numero_factura')->nullable();

            // Copia de datos del paciente
            $table->string('paciente_nombre')->nullable();
            $table->string('paciente_ci', 50)->nullable();
            $table->string('paciente_telefono', 100)->nullable();
            $table->string('paciente_direccion', 255)->nullable();
            $table->date('paciente_fecha_nac')->nullable();
            $table->string('paciente_genero', 10)->nullable();
            $table->unsignedInteger('paciente_edad')->nullable();

            // Copia de datos del doctor
            $table->string('doctor_nombre')->nullable();
            $table->string('doctor_especialidad')->nullable();
            $table->string('doctor_ci', 50)->nullable();
            $table->string('doctor_telefono', 100)->nullable();
            $table->string('doctor_email')->nullable();
            $table->string('doctor_registro')->nullable();
            $table->string('muestra_rechazada')->nullable();
            $table->string('muestra_observacion')->nullable();

            $table->unsignedBigInteger('establecimiento_id')->nullable();
            $table->foreign('establecimiento_id')
                ->references('id')
                ->on('establecimientos')
                ->onDelete('cascade');

            // Usuario que registró
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users');
//            usuario preanalitica
            $table->foreignId('user_preanalitica_id')
                ->nullable()
                ->constrained('users');
//            user_analitica_id
            $table->foreignId('user_analitica_id')
                ->nullable()
                ->constrained('users');

            $table->string('muestra_sangre_entera')->nullable();      // ACEPTADA / RECHAZADA
            $table->string('muestra_coagulo')->nullable();            // SI / NO
            $table->string('muestra_volumen')->nullable();            // SI / NO
            $table->string('muestra_identificacion')->nullable();
            $table->string('motivo_rechazo')->nullable();

            // Equipo (Mindray C3510 / Mindray 5000, etc.)
            $table->string('muestra_equipo')->nullable();
            $table->unsignedBigInteger('establecimiento_origen_id')->nullable();
            $table->foreign('establecimiento_origen_id')
                ->references('id')
                ->on('establecimientos');

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitudes');
    }
};
