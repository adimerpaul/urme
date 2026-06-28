<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            // Eliminar guiones del campo ci en todos los pacientes activos
            DB::statement("UPDATE pacientes SET ci = REPLACE(ci, '-', '') WHERE ci LIKE '%-%' AND deleted_at IS NULL");

            // Volver a mergear duplicados que hayan quedado expuestos tras la limpieza
            $this->mergeDuplicates(
                groupBy: ['ci'],
                condition: fn ($q) => $q->whereNotNull('ci')->where('ci', '!=', '')
            );

            $this->mergeDuplicates(
                groupBy: ['nombre_completo', 'fecha_nac'],
                condition: fn ($q) => $q->where(fn ($q2) => $q2->whereNull('ci')->orWhere('ci', ''))
                                        ->whereNotNull('nombre_completo')
                                        ->whereNotNull('fecha_nac')
            );
        });
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
        // Irreversible: no se pueden restaurar guiones ni reasignaciones anteriores.
    }
};
