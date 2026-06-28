<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $this->normalizarTodo();
            $this->fusionarDuplicados();
        });
    }

    /**
     * Normaliza ci y nombre_completo de todos los pacientes activos.
     * Persiste los valores limpios en la BD para que futuros merges por SQL también funcionen.
     */
    private function normalizarTodo(): void
    {
        DB::table('pacientes')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->chunk(500, function ($rows) {
                foreach ($rows as $p) {
                    $updates = [];

                    $ciClean = $this->normalizarCI($p->ci ?? '');
                    if ($ciClean !== ($p->ci ?? '')) {
                        $updates['ci'] = $ciClean ?: null;
                    }

                    $nombreClean = $this->normalizarNombre($p->nombre_completo ?? '');
                    if ($nombreClean !== ($p->nombre_completo ?? '')) {
                        $updates['nombre_completo'] = $nombreClean;
                    }

                    if (!empty($updates)) {
                        DB::table('pacientes')->where('id', $p->id)->update($updates);
                    }
                }
            });
    }

    /** Quita puntos, guiones y espacios del CI. */
    private function normalizarCI(string $ci): string
    {
        return strtoupper(trim(str_replace(['.', '-', ' '], '', $ci)));
    }

    /**
     * Limpia el nombre:
     *  - Quita puntos y guiones (los convierte en espacio)
     *  - Colapsa espacios múltiples
     *  - Elimina espacios entre letras iniciales sueltas: "R N ARISMENDI" → "RN ARISMENDI"
     */
    private function normalizarNombre(string $nombre): string
    {
        $n = str_replace(['.', '-'], ' ', $nombre);
        $n = preg_replace('/\s+/', ' ', trim($n));
        $n = strtoupper($n);

        // Colapsar iniciales sueltas consecutivas: "R N A PEREZ" → "RNA PEREZ"
        // Repetir hasta que no haya más cambios
        do {
            $prev = $n;
            $n = preg_replace('/\b([A-ZÁÉÍÓÚÜÑ]) ([A-ZÁÉÍÓÚÜÑ])\b/', '$1$2', $n);
        } while ($n !== $prev);

        return trim($n);
    }

    /**
     * Carga todos los pacientes activos en PHP, los agrupa por clave normalizada
     * y soft-elimina los duplicados reasignando sus solicitudes.
     *
     * Orden de prioridad de clave:
     *   1. ci (si tiene)
     *   2. codigo (si no tiene ci)
     *   3. nombre_completo + fecha_nac (fallback)
     */
    private function fusionarDuplicados(): void
    {
        $pacientes = DB::table('pacientes')
            ->whereNull('deleted_at')
            ->select(['id', 'ci', 'codigo', 'nombre_completo', 'fecha_nac'])
            ->orderBy('id')
            ->get();

        // Contar solicitudes por paciente para elegir el que se conserva
        $solicitudesCount = DB::table('solicitudes')
            ->selectRaw('paciente_id, COUNT(*) as total')
            ->groupBy('paciente_id')
            ->pluck('total', 'paciente_id');

        // Agrupar por clave de deduplicación
        $grupos = [];
        foreach ($pacientes as $p) {
            $key = $this->claveDedup($p);
            $grupos[$key][] = $p;
        }

        foreach ($grupos as $key => $grupo) {
            if (count($grupo) <= 1) {
                continue;
            }

            // Elegir el que tiene más solicitudes; si empate, el de menor id
            usort($grupo, function ($a, $b) use ($solicitudesCount) {
                $sa = $solicitudesCount[$a->id] ?? 0;
                $sb = $solicitudesCount[$b->id] ?? 0;
                if ($sa !== $sb) return $sb - $sa;
                return $a->id - $b->id;
            });

            $keepId       = $grupo[0]->id;
            $duplicateIds = array_slice(array_column($grupo, 'id'), 1);

            DB::table('solicitudes')
                ->whereIn('paciente_id', $duplicateIds)
                ->update(['paciente_id' => $keepId]);

            DB::table('pacientes')
                ->whereIn('id', $duplicateIds)
                ->update(['deleted_at' => now()]);
        }
    }

    private function claveDedup(object $p): string
    {
        $ci = $this->normalizarCI($p->ci ?? '');
        if ($ci !== '') {
            return 'ci:' . $ci;
        }

        $codigo = trim($p->codigo ?? '');
        if ($codigo !== '') {
            return 'cod:' . $codigo;
        }

        $nombre   = $this->normalizarNombre($p->nombre_completo ?? '');
        $fechaNac = $p->fecha_nac ?? '';
        return 'nom:' . $nombre . '|' . $fechaNac;
    }

    public function down(): void
    {
        // Irreversible.
    }
};
