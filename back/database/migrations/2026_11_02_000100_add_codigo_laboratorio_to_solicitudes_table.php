<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Código laboratorio ingresado a mano (p. ej. A19). Nullable solo para las
     * solicitudes existentes; la API lo exige al crear y al editar. No es único.
     */
    public function up(): void
    {
        Schema::table('solicitudes', function (Blueprint $table) {
            $table->string('codigo_laboratorio', 20)->nullable()->after('codigo_verificacion');
            $table->index('codigo_laboratorio');
        });
    }

    public function down(): void
    {
        Schema::table('solicitudes', function (Blueprint $table) {
            $table->dropIndex(['codigo_laboratorio']);
            $table->dropColumn('codigo_laboratorio');
        });
    }
};
