<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'unidad_solicitante_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('unidad_solicitante_id')
                    ->nullable()
                    ->after('establecimiento_id')
                    ->constrained('unidad_solicitantes')
                    ->nullOnDelete();
            });
        }

        $nombreUnidad = 'DEPTO. DE INGENIERÍA Y MANTENIMIENTO HGSJDD BLOQUE ORURO COREA';
        $unidadExiste = DB::table('unidad_solicitantes')
            ->where('nombre', $nombreUnidad)
            ->exists();

        if (! $unidadExiste) {
            DB::table('unidad_solicitantes')->insert([
                'nombre' => $nombreUnidad,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'unidad_solicitante_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropConstrainedForeignId('unidad_solicitante_id');
            });
        }
    }
};
