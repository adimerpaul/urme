<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $this->cleanNombres();
            $this->mergeByCI();
            $this->mergeByCodigo();
            $this->mergeByNombreFechaNac();
        });
    }

    /**
     * Elimina puntos y guiones del nombre_completo y colapsa espacios múltiples.
     * Ej: "R.N. ARISMENDI" → "RN ARISMENDI"  |  "RN-ARISMENDI" → "RN ARISMENDI"
     */
    private function cleanNombres(): void
    {
        $pacientes = DB::table('pacientes')
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->where('nombre_completo', 'like', '%-%')
                  ->orWhere('nombre_completo', 'like', '%.%');
            })
            ->get(['id', 'nombre_completo']);

        foreach ($pacientes as $p) {
            // Eliminar puntos y guiones, colapsar espacios, trim, uppercase
            $cleaned = str_replace(['.', '-'], ' ', $p->nombre_completo);
            $cleaned = preg_replace('/\s{2,}/', ' ', $cleaned);
            $cleaned = strtoupper(trim($cleaned));

            if ($cleaned !== $p->nombre_completo) {
                DB::table('pacientes')->where('id', $p->id)->update(['nombre_completo' => $cleaned]);
            }
        }
    }

    /**
     * Mergea pacientes con mismo CI (después de la limpieza previa de guiones).
     */
    private function mergeByCI(): void
    {
        $this->mergeDuplicates(
            groupBy: ['ci'],
            condition: fn ($q) => $q->whereNotNull('ci')->where('ci', '!=', '')
        );
    }

    /**
     * Mergea pacientes sin CI que comparten el mismo codigo generado.
     * El codigo encapsula iniciales + fecha de nacimiento, es suficientemente único
     * para identificar duplicados en pacientes sin documento.
     */
    private function mergeByCodigo(): void
    {
        $this->mergeDuplicates(
            groupBy: ['codigo'],
            condition: fn ($q) => $q->where(fn ($q2) => $q2->whereNull('ci')->orWhere('ci', ''))
                                    ->whereNotNull('codigo')
                                    ->where('codigo', '!=', '')
        );
    }

    /**
     * Mergea por nombre + fecha_nac como último recurso (cubre casos sin CI ni codigo).
     */
    private function mergeByNombreFechaNac(): void
    {
        $this->mergeDuplicates(
            groupBy: ['nombre_completo', 'fecha_nac'],
            condition: fn ($q) => $q->where(fn ($q2) => $q2->whereNull('ci')->orWhere('ci', ''))
                                    ->whereNotNull('nombre_completo')
                                    ->whereNotNull('fecha_nac')
        );
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
