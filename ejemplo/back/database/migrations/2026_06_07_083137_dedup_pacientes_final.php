<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $this->normalizarCI();
            $this->normalizarNombres();

            // Orden de prioridad: CI > codigo > nombre+fecha_nac
            $this->mergeDuplicates(
                groupBy: ['ci'],
                condition: fn ($q) => $q->whereNotNull('ci')->where('ci', '!=', '')
            );

            $this->mergeDuplicates(
                groupBy: ['codigo'],
                condition: fn ($q) => $q->where(fn ($q2) => $q2->whereNull('ci')->orWhere('ci', ''))
                                        ->whereNotNull('codigo')->where('codigo', '!=', '')
            );

            $this->mergeDuplicates(
                groupBy: ['nombre_completo', 'fecha_nac'],
                condition: fn ($q) => $q->where(fn ($q2) => $q2->whereNull('ci')->orWhere('ci', ''))
                                        ->whereNotNull('nombre_completo')
                                        ->whereNotNull('fecha_nac')
            );
        });
    }

    /** Elimina puntos, guiones y espacios del CI. Ej: "1.234-567 A" → "1234567A" */
    private function normalizarCI(): void
    {
        $pacientes = DB::table('pacientes')
            ->whereNull('deleted_at')
            ->whereNotNull('ci')
            ->where('ci', '!=', '')
            ->where(function ($q) {
                $q->where('ci', 'like', '%-%')
                  ->orWhere('ci', 'like', '%.%')
                  ->orWhere('ci', 'like', '% %');
            })
            ->get(['id', 'ci']);

        foreach ($pacientes as $p) {
            $cleaned = strtoupper(trim(str_replace(['.', '-', ' '], '', $p->ci)));
            if ($cleaned !== $p->ci) {
                DB::table('pacientes')->where('id', $p->id)->update(['ci' => $cleaned]);
            }
        }
    }

    /** Elimina puntos y guiones del nombre_completo y colapsa espacios. Ej: "R.N. ARISMENDI" → "RN ARISMENDI" */
    private function normalizarNombres(): void
    {
        $pacientes = DB::table('pacientes')
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->where('nombre_completo', 'like', '%-%')
                  ->orWhere('nombre_completo', 'like', '%.%');
            })
            ->get(['id', 'nombre_completo']);

        foreach ($pacientes as $p) {
            $cleaned = str_replace(['.', '-'], ' ', $p->nombre_completo);
            $cleaned = strtoupper(trim(preg_replace('/\s{2,}/', ' ', $cleaned)));
            if ($cleaned !== $p->nombre_completo) {
                DB::table('pacientes')->where('id', $p->id)->update(['nombre_completo' => $cleaned]);
            }
        }
    }

    private function mergeDuplicates(array $groupBy, callable $condition): void
    {
        $groups = DB::table('pacientes')
            ->tap($condition)
            ->whereNull('deleted_at')
            ->groupBy(...$groupBy)
            ->having(DB::raw('COUNT(*)'), '>', 1)
            ->select([...$groupBy, DB::raw('COUNT(*) as total')])
            ->get();

        foreach ($groups as $group) {
            // Se conserva el paciente con más solicitudes; si empate, el de menor id
            $duplicates = DB::table('pacientes')
                ->whereNull('pacientes.deleted_at')
                ->tap(function ($q) use ($groupBy, $group) {
                    foreach ($groupBy as $col) {
                        $q->where("pacientes.$col", $group->$col);
                    }
                })
                ->select([
                    'pacientes.id',
                    DB::raw('COUNT(solicitudes.id) as total_solicitudes'),
                ])
                ->leftJoin('solicitudes', 'solicitudes.paciente_id', '=', 'pacientes.id')
                ->groupBy('pacientes.id')
                ->orderByDesc('total_solicitudes')
                ->orderBy('pacientes.id')
                ->get();

            if ($duplicates->count() <= 1) {
                continue;
            }

            $keepId       = $duplicates->first()->id;
            $duplicateIds = $duplicates->skip(1)->pluck('id')->toArray();

            DB::table('solicitudes')
                ->whereIn('paciente_id', $duplicateIds)
                ->update(['paciente_id' => $keepId]);

            DB::table('pacientes')
                ->whereIn('id', $duplicateIds)
                ->update(['deleted_at' => now()]);
        }
    }

    public function down(): void
    {
        // Irreversible.
    }
};
