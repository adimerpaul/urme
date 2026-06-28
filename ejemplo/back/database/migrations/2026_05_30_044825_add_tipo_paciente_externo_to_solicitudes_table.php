<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solicitudes', function (Blueprint $table) {
            $table->string('tipo_paciente_externo')->nullable()->after('tipo_atencion');
            $table->string('autorizado_por')->nullable()->after('tipo_paciente_externo');
        });
    }

    public function down(): void
    {
        Schema::table('solicitudes', function (Blueprint $table) {
            $table->dropColumn(['tipo_paciente_externo', 'autorizado_por']);
        });
    }
};
