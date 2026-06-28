<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solicitudes', function (Blueprint $table) {
            $table->foreignId('unidad_solicitante_id')
                ->nullable()
                ->after('sala')
                ->constrained('unidad_solicitantes');
        });

        $nombres = DB::table('solicitudes')
            ->whereNotNull('sala')
            ->whereRaw("TRIM(sala) <> ''")
            ->select('sala')
            ->distinct()
            ->pluck('sala');

        foreach ($nombres as $nombre) {
            $nombre = trim((string) $nombre);

            if ($nombre === '') {
                continue;
            }

            $id = DB::table('unidad_solicitantes')
                ->where('nombre', $nombre)
                ->value('id');

            if (!$id) {
                $id = DB::table('unidad_solicitantes')->insertGetId([
                    'nombre' => $nombre,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('solicitudes')
                ->where('sala', $nombre)
                ->update(['unidad_solicitante_id' => $id]);
        }
    }

    public function down(): void
    {
        Schema::table('solicitudes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('unidad_solicitante_id');
        });
    }
};
