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
        Schema::table('solicitudes', function (Blueprint $table) {
            $table->boolean('paciente_discapacidad')->nullable()->after('paciente_edad');
            $table->string('paciente_discapacidad_cual', 120)->nullable()->after('paciente_discapacidad');
            $table->string('paciente_discapacidad_otro', 120)->nullable()->after('paciente_discapacidad_cual');
            $table->boolean('paciente_embarazo')->nullable()->after('paciente_discapacidad_otro');
            $table->date('paciente_fum')->nullable()->after('paciente_embarazo');
            $table->unsignedTinyInteger('paciente_sem_gest')->nullable()->after('paciente_fum');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solicitudes', function (Blueprint $table) {
            $table->dropColumn([
                'paciente_discapacidad',
                'paciente_discapacidad_cual',
                'paciente_discapacidad_otro',
                'paciente_embarazo',
                'paciente_fum',
                'paciente_sem_gest',
            ]);
        });
    }
};
