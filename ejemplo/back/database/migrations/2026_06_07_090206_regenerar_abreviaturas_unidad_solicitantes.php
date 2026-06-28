<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Limpiar para evitar conflictos del índice único durante la regeneración
        DB::table('unidad_solicitantes')->whereNull('deleted_at')->update(['abreviatura' => null]);

        $unidades = DB::table('unidad_solicitantes')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get(['id', 'nombre']);

        $usadas = [];
        foreach ($unidades as $u) {
            $palabras = array_values(array_filter(
                preg_split('/\s+/u', trim($u->nombre)),
                fn ($p) => $p !== ''
            ));

            if (count($palabras) === 0) {
                $base = 'SIN';
            } elseif (count($palabras) === 1) {
                // Una sola palabra → primeras 4 letras. Ej: "Urgencias" → "URGE"
                $base = strtoupper(mb_substr($palabras[0], 0, 4, 'UTF-8'));
            } else {
                // Varias palabras → inicial de cada palabra. Ej: "Cirugía Pediátrica" → "CP"
                $base = strtoupper(implode('', array_map(
                    fn ($p) => mb_substr($p, 0, 1, 'UTF-8'),
                    $palabras
                )));
            }

            // Garantizar unicidad
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
        // Irreversible.
    }
};
