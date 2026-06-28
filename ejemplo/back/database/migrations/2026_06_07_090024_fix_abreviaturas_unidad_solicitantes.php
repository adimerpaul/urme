<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Regenerar todas las abreviaturas con la lógica mejorada:
        // - 1 palabra  → primeras 4 letras  (ej: "Urgencias" → "URGE")
        // - 2+ palabras → inicial de cada palabra (ej: "Cirugía Pediátrica" → "CP")
        // Unicidad: si colisiona añadir sufijo numérico (CP2, CP3…)

        $unidades = DB::table('unidad_solicitantes')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get(['id', 'nombre']);

        // Primero limpiar todas para evitar conflictos de unique durante el update
        DB::table('unidad_solicitantes')->whereNull('deleted_at')->update(['abreviatura' => null]);

        $usadas = [];
        foreach ($unidades as $u) {
            $palabras = array_values(array_filter(
                preg_split('/\s+/u', trim($u->nombre)),
                fn ($p) => $p !== ''
            ));

            if (count($palabras) === 0) {
                $base = 'SIN';
            } elseif (count($palabras) === 1) {
                $base = strtoupper(mb_substr($palabras[0], 0, 4, 'UTF-8'));
            } else {
                $base = strtoupper(implode('', array_map(
                    fn ($p) => mb_substr($p, 0, 1, 'UTF-8'),
                    $palabras
                )));
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
        // Irreversible.
    }
};
