<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unidad_solicitantes', function (Blueprint $table) {
            $table->string('abreviatura', 20)->nullable()->unique()->after('nombre');
        });

        // Generar abreviaturas automáticas: iniciales de cada palabra, mayúsculas
        // Ej: "Cirugía Pediátrica" → "CP", si hay colisión → "CP2", "CP3", ...
        $unidades = DB::table('unidad_solicitantes')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get(['id', 'nombre']);

        $usadas = [];
        foreach ($unidades as $u) {
            $palabras = preg_split('/\s+/u', trim($u->nombre));
            $base = strtoupper(implode('', array_map(
                fn ($p) => mb_substr($p, 0, 1, 'UTF-8'),
                array_filter($palabras, fn ($p) => $p !== '')
            )));

            if ($base === '') {
                $base = strtoupper(mb_substr(trim($u->nombre), 0, 3, 'UTF-8'));
            }

            $abrev = $base;
            $n = 2;
            while (in_array($abrev, $usadas)) {
                $abrev = $base . $n++;
            }
            $usadas[] = $abrev;

            DB::table('unidad_solicitantes')->where('id', $u->id)->update(['abreviatura' => $abrev]);
        }
    }

    public function down(): void
    {
        Schema::table('unidad_solicitantes', function (Blueprint $table) {
            $table->dropUnique(['abreviatura']);
            $table->dropColumn('abreviatura');
        });
    }
};
