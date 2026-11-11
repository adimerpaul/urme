<?php

namespace App\Http\Controllers;

use App\Exports\LaboratorioReporteExport;
use App\Models\Solicitude;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class LaboratorioReporteController extends Controller
{
    public function index(Request $request)
    {
        [$query, $filters] = $this->query($request);
        $resumen = [
            'pruebas' => (clone $query)->where('clase', 'examen')->count(),
            'perfiles' => (clone $query)->where('clase', 'perfil')->count(),
            'solicitudes' => (clone $query)->distinct()->count('solicitude_id'),
            'importe' => (float) (clone $query)->sum('precio'),
        ];
        $pagina = $query->paginate((int) $request->input('per_page', 25));
        $pagina->setCollection($this->conSolicitudes($pagina->getCollection()));

        return response()->json([
            ...$pagina->toArray(),
            'resumen' => $resumen,
            'filtros' => $filters,
        ]);
    }

    public function excel(Request $request)
    {
        [$query, $filters] = $this->query($request);

        return Excel::download(
            new LaboratorioReporteExport($this->conSolicitudes($query->get())),
            "laboratorios_{$filters['desde']}_{$filters['hasta']}.xlsx"
        );
    }

    public function pdf(Request $request)
    {
        [$query, $filters] = $this->query($request);

        return Pdf::loadView('reportes.laboratorios-general', [
            'items' => $this->conSolicitudes($query->get()), 'filters' => $filters,
        ])->setPaper('letter', 'landscape')->download("laboratorios_{$filters['desde']}_{$filters['hasta']}.pdf");
    }

    /**
     * Filas del reporte: cada examen de la solicitud y cada perfil (con su precio).
     * Los exámenes de un perfil van con precio 0 y perfil_nombre, para mostrarse
     * como "Incluido en …"; así el importe suma cada cosa una sola vez. El perfil
     * va antes que sus exámenes (comparte el orden del primero).
     */
    private function query(Request $request): array
    {
        abort_unless($request->user()->hasPermissionTo('Ver Solicitudes Laboratorio'), 403, 'No tiene permiso para realizar esta acción');
        $request->merge([
            'desde' => $request->input('desde', now()->subMonthNoOverflow()->startOfMonth()->toDateString()),
            'hasta' => $request->input('hasta', now()->subMonthNoOverflow()->endOfMonth()->toDateString()),
        ]);
        $filters = $request->validate([
            'desde' => ['required', 'date_format:Y-m-d'],
            'hasta' => ['required', 'date_format:Y-m-d', 'after_or_equal:desde'],
            'nombre' => ['nullable', 'string', 'max:255'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);
        $nombre = trim($filters['nombre'] ?? '');
        $enRango = fn ($query, string $alias) => $query
            ->join('solicitudes as s', 's.id', '=', "{$alias}.solicitude_id")
            ->whereNull('s.deleted_at')
            ->whereNull("{$alias}.deleted_at")
            ->whereBetween('s.fecha_solicitud', [$filters['desde'], $filters['hasta']]);

        $examenes = $enRango(DB::table('solicitud_laboratorio_items as i'), 'i')
            ->leftJoin('solicitud_laboratorio_perfiles as sp', function ($join) {
                $join->on('sp.id', '=', 'i.solicitud_laboratorio_perfil_id')->whereNull('sp.deleted_at');
            })
            ->when($nombre !== '', fn ($query) => $query->where(fn ($filtro) => $filtro
                ->where('i.producto_nombre', 'like', '%'.$nombre.'%')
                ->orWhere('sp.nombre', 'like', '%'.$nombre.'%')))
            ->selectRaw("i.id, 'examen' as clase, i.solicitude_id, i.producto_nombre, i.precio, i.orden, sp.nombre as perfil_nombre");

        $perfiles = $enRango(DB::table('solicitud_laboratorio_perfiles as p'), 'p')
            ->when($nombre !== '', fn ($query) => $query->where('p.nombre', 'like', '%'.$nombre.'%'))
            ->selectRaw("p.id, 'perfil' as clase, p.solicitude_id, p.nombre as producto_nombre, p.precio, p.orden, NULL as perfil_nombre");

        $query = DB::query()->fromSub($examenes->unionAll($perfiles), 'filas')
            ->orderByDesc('solicitude_id')
            ->orderBy('orden')
            ->orderByRaw("clase = 'perfil' DESC")
            ->orderBy('id');

        return [$query, $filters];
    }

    /** Agrega a cada fila su solicitud (con el paciente), como antes daba la relación. */
    private function conSolicitudes($filas)
    {
        $solicitudes = Solicitude::with('paciente:id,nombre_completo,ci')
            ->whereIn('id', $filas->pluck('solicitude_id')->unique())
            ->get()
            ->keyBy('id');

        return $filas->map(function ($fila) use ($solicitudes) {
            $fila->precio = number_format((float) $fila->precio, 2, '.', '');
            $fila->solicitude = $solicitudes->get($fila->solicitude_id);

            return $fila;
        });
    }
}
