<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            // Solo aplica a pacientes SIN CI — para los que tienen CI diferente
            // el nombre igual no es criterio suficiente (pueden ser personas distintas).
            $pacientes = DB::table('pacientes')
                ->whereNull('deleted_at')
                ->where(fn ($q) => $q->whereNull('ci')->orWhere('ci', ''))
                ->whereNotNull('nombre_completo')
                ->select(['id', 'nombre_completo'])
                ->orderBy('id')
                ->get();

            $solicitudesCount = DB::table('solicitudes')
                ->selectRaw('paciente_id, COUNT(*) as total')
                ->groupBy('paciente_id')
                ->pluck('total', 'paciente_id');

            // Agrupar por nombre normalizado
            $grupos = [];
            foreach ($pacientes as $p) {
                $key = $this->normalizarNombre($p->nombre_completo);
                $grupos[$key][] = $p->id;
            }

            foreach ($grupos as $nombre => $ids) {
                if (count($ids) <= 1) {
                    continue;
                }

                // Conservar el que tiene más solicitudes; si empate, el de menor id
                usort($ids, function ($a, $b) use ($solicitudesCount) {
                    $sa = $solicitudesCount[$a] ?? 0;
                    $sb = $solicitudesCount[$b] ?? 0;
                    if ($sa !== $sb) return $sb - $sa;
                    return $a - $b;
                });

                $keepId       = $ids[0];
                $duplicateIds = array_slice($ids, 1);

                DB::table('solicitudes')
                    ->whereIn('paciente_id', $duplicateIds)
                    ->update(['paciente_id' => $keepId]);

                DB::table('pacientes')
                    ->whereIn('id', $duplicateIds)
                    ->update(['deleted_at' => now()]);
            }
        });
    }

    private function normalizarNombre(string $nombre): string
    {
        $n = str_replace(['.', '-'], ' ', $nombre);
        $n = preg_replace('/\s+/', ' ', trim($n));
        $n = strtoupper($n);

        // Colapsar iniciales sueltas consecutivas: "R N" → "RN"
        do {
            $prev = $n;
            $n = preg_replace('/\b([A-ZÁÉÍÓÚÜÑ]) ([A-ZÁÉÍÓÚÜÑ])\b/', '$1$2', $n);
        } while ($n !== $prev);

        return $n;
    }

    public function down(): void
    {
        // Irreversible.
    }
};
