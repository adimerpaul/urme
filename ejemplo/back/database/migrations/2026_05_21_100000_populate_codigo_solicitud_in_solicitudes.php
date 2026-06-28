<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Poblar codigo_solicitud = nro_registro || codigo
        // solo donde alguno de los dos existe y codigo_solicitud está vacío
        DB::statement("
            UPDATE solicitudes
            SET codigo_solicitud = CONCAT(COALESCE(nro_registro, ''), COALESCE(codigo, ''))
            WHERE (codigo_solicitud IS NULL OR codigo_solicitud = '')
              AND (nro_registro IS NOT NULL OR codigo IS NOT NULL)
              AND deleted_at IS NULL
        ");
    }

    public function down(): void
    {
        // No reversible: no queremos borrar datos existentes
    }
};
