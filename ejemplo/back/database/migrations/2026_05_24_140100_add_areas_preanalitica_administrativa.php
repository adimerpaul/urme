<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $areas = [
            'AREA PREANALITICA',
            'AREA ADMINISTRATIVA',
        ];

        foreach ($areas as $nombre) {
            $exists = DB::table('areas')
                ->where('name', $nombre)
                ->orWhere('title', $nombre)
                ->exists();

            if (! $exists) {
                DB::table('areas')->insert([
                    'name'        => $nombre,
                    'descripcion' => null,
                    'title'       => $nombre,
                    'estado'      => 'ACTIVO',
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('areas')
            ->whereIn('name', ['AREA PREANALITICA', 'AREA ADMINISTRATIVA'])
            ->delete();
    }
};
