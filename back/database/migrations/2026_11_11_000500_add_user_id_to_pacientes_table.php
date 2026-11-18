<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Quién registró al paciente (pacientes.user_id). La fecha y hora ya la guarda
 * created_at. El código lo llena al crear un paciente (PacienteController::store).
 *
 * Solo esquema. La versión original también rellenaba los pacientes existentes
 * con el usuario de su auditoría de creación; ese relleno se quitó a propósito
 * (decisión del 2026-11-18, opción 2): en producción no se modifican datos
 * existentes por migración. Los pacientes anteriores quedan con user_id null y
 * la pantalla muestra «—» en «Registrado por». Ver docs/PENDIENTES.md.
 *
 * Si en algún entorno ya se ejecutó la versión original, el relleno ya está
 * hecho allí y esta migración no vuelve a correr: no cambia nada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pacientes', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pacientes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
