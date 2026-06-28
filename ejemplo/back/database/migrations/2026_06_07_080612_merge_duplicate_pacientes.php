<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            // Duplicados por CI
            $this->mergeDuplicates(
                groupBy: ['ci'],
                condition: fn ($q) => $q->whereNotNull('ci')->where('ci', '!=', '')
            );

            // Duplicados por nombre + fecha de nacimiento (pacientes sin CI)
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
            // Ordenar: primero el que tiene más solicitudes; si empate, el de menor id
            $duplicates = DB::table('pacientes')
                ->whereNull('pacientes.deleted_at')
                ->tap(function ($q) use ($groupBy, $group) {
                    foreach ($groupBy as $col) {
                        $q->where($col, $group->$col);
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

            $keepId      = $duplicates->first()->id;
            $duplicateIds = $duplicates->skip(1)->pluck('id')->toArray();

            // Reasignar solicitudes de los duplicados al paciente conservado
            DB::table('solicitudes')
                ->whereIn('paciente_id', $duplicateIds)
                ->update(['paciente_id' => $keepId]);

            // Soft-delete de los duplicados
            DB::table('pacientes')
                ->whereIn('id', $duplicateIds)
                ->update(['deleted_at' => now()]);
        }
    }

    public function down(): void
    {
        // Irreversible: las solicitudes reasignadas no pueden volver automáticamente.
    }
};
