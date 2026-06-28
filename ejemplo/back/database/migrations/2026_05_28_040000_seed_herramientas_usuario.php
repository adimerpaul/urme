<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach (['fecha_inicio_registro', 'fecha_fin_registro'] as $nombre) {
            DB::table('herramientas_usuario')
                ->updateOrInsert(
                    ['nombre' => $nombre],
                    ['valor' => null, 'created_at' => $now, 'updated_at' => $now]
                );
        }
    }

    public function down(): void
    {
        DB::table('herramientas_usuario')
            ->whereIn('nombre', ['fecha_inicio_registro', 'fecha_fin_registro'])
            ->delete();
    }
};
