<?php

namespace App\Http\Controllers;

use App\Exports\FabricantesExport;
use App\Exports\ProductosExport;
use App\Exports\UnidadesExport;
use App\Models\Fabricante;
use App\Models\Producto;
use App\Models\Unidad;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ProductoController extends Controller
{
    // ── Resumen ───────────────────────────────────────────────────

    public function resumen(Request $request)
    {
        $this->req($request, 'Ver Productos');
        return response()->json([
            'productos'   => Producto::where('tipo', 'FARMACIA')->count(),
            'fabricantes' => Fabricante::count(),
            'unidades'    => Unidad::count(),
        ]);
    }

    // ── Catálogos ─────────────────────────────────────────────────

    public function fabricantes(Request $request)
    {
        $this->req($request, 'Ver Productos');
        $q       = $request->input('q', '');
        $perPage = $request->input('per_page');

        $query = Fabricante::orderBy('nombre');
        if ($q) {
            $query->where(function ($sq) use ($q) {
                $sq->where('nombre', 'like', "%$q%")
                   ->orWhere('pais',   'like', "%$q%");
            });
        }

        if ($perPage) {
            return response()->json($query->paginate((int) $perPage));
        }
        return response()->json($query->get());
    }

    public function storeFabricante(Request $request)
    {
        $this->req($request, 'Crear Productos');
        $request->validate(['nombre' => 'required|string|max:255']);
        $fab = Fabricante::create([
            'nombre' => mb_strtoupper($request->nombre),
            'pais'   => $request->pais ? mb_strtoupper($request->pais) : null,
        ]);
        return response()->json($fab, 201);
    }

    public function updateFabricante(Request $request, $id)
    {
        $this->req($request, 'Editar Productos');
        $request->validate(['nombre' => 'required|string|max:255']);
        $fab = Fabricante::findOrFail($id);
        $fab->update([
            'nombre' => mb_strtoupper($request->nombre),
            'pais'   => $request->pais ? mb_strtoupper($request->pais) : null,
        ]);
        return response()->json($fab);
    }

    public function destroyFabricante(Request $request, $id)
    {
        $this->req($request, 'Eliminar Productos');
        Fabricante::findOrFail($id)->delete();
        return response()->json(['message' => 'Fabricante eliminado']);
    }

    public function unidades(Request $request)
    {
        $this->req($request, 'Ver Productos');
        $q       = $request->input('q', '');
        $perPage = $request->input('per_page');

        $query = Unidad::orderBy('nombre');
        if ($q) {
            $query->where(function ($sq) use ($q) {
                $sq->where('nombre',       'like', "%$q%")
                   ->orWhere('abreviatura', 'like', "%$q%");
            });
        }

        if ($perPage) {
            return response()->json($query->paginate((int) $perPage));
        }
        return response()->json($query->get());
    }

    public function storeUnidad(Request $request)
    {
        $this->req($request, 'Crear Productos');
        $request->validate(['nombre' => 'required|string|max:255']);
        $u = Unidad::create([
            'nombre'      => mb_strtoupper($request->nombre),
            'abreviatura' => $request->abreviatura ? mb_strtoupper($request->abreviatura) : null,
        ]);
        return response()->json($u, 201);
    }

    public function updateUnidad(Request $request, $id)
    {
        $this->req($request, 'Editar Productos');
        $request->validate(['nombre' => 'required|string|max:255']);
        $u = Unidad::findOrFail($id);
        $u->update([
            'nombre'      => mb_strtoupper($request->nombre),
            'abreviatura' => $request->abreviatura ? mb_strtoupper($request->abreviatura) : null,
        ]);
        return response()->json($u);
    }

    public function destroyUnidad(Request $request, $id)
    {
        $this->req($request, 'Eliminar Productos');
        Unidad::findOrFail($id)->delete();
        return response()->json(['message' => 'Unidad eliminada']);
    }

    // ── Productos ─────────────────────────────────────────────────

    public function index(Request $request)
    {
        $this->req($request, 'Ver Productos');

        $q       = $request->input('q', '');
        $tipo    = $request->input('tipo', '');
        $perPage = (int) $request->input('per_page', 20);

        $query = Producto::with(['fabricante:id,nombre', 'unidad:id,nombre,abreviatura'])
            ->orderBy('nombre');

        if ($q) {
            $query->where(function ($sq) use ($q) {
                $sq->where('nombre',  'like', "%$q%")
                   ->orWhere('codigo', 'like', "%$q%")
                   ->orWhere('marca',  'like', "%$q%");
            });
        }

        if ($tipo) {
            $query->where('tipo', $tipo);
        }

        return response()->json($query->paginate($perPage));
    }

    public function store(Request $request)
    {
        $this->req($request, 'Crear Productos');
        $request->validate([
            'nombre' => 'required|string|max:255',
            'tipo'   => 'required|string|max:80',
        ]);
        $producto = Producto::create([
            'codigo'        => $request->codigo       ? mb_strtoupper($request->codigo)      : null,
            'nombre'        => mb_strtoupper($request->nombre),
            'descripcion'   => $request->descripcion  ? mb_strtoupper($request->descripcion) : null,
            'marca'         => $request->marca         ? mb_strtoupper($request->marca)        : null,
            'fabricante_id' => $request->fabricante_id ?: null,
            'unidad_id'     => $request->unidad_id     ?: null,
            'tipo'          => mb_strtoupper($request->tipo),
        ]);
        return response()->json($producto->load(['fabricante:id,nombre', 'unidad:id,nombre,abreviatura']), 201);
    }

    public function update(Request $request, $id)
    {
        $this->req($request, 'Editar Productos');
        $producto = Producto::findOrFail($id);
        $request->validate([
            'nombre' => 'required|string|max:255',
            'tipo'   => 'required|string|max:80',
        ]);
        $producto->update([
            'codigo'        => $request->codigo       ? mb_strtoupper($request->codigo)      : null,
            'nombre'        => mb_strtoupper($request->nombre),
            'descripcion'   => $request->descripcion  ? mb_strtoupper($request->descripcion) : null,
            'marca'         => $request->marca         ? mb_strtoupper($request->marca)        : null,
            'fabricante_id' => $request->fabricante_id ?: null,
            'unidad_id'     => $request->unidad_id     ?: null,
            'tipo'          => mb_strtoupper($request->tipo),
        ]);
        return response()->json($producto->load(['fabricante:id,nombre', 'unidad:id,nombre,abreviatura']));
    }

    public function destroy(Request $request, $id)
    {
        $this->req($request, 'Eliminar Productos');
        Producto::findOrFail($id)->delete();
        return response()->json(['message' => 'Producto eliminado']);
    }

    // ── Exportar Productos ────────────────────────────────────────

    public function exportProductosPdf(Request $request)
    {
        $this->req($request, 'Ver Productos');
        $q    = $request->input('q', '');
        $tipo = $request->input('tipo', 'FARMACIA');

        $query = Producto::with(['fabricante:id,nombre', 'unidad:id,nombre,abreviatura'])
            ->orderBy('nombre');
        if ($q) {
            $query->where(function ($sq) use ($q) {
                $sq->where('nombre', 'like', "%$q%")
                   ->orWhere('codigo', 'like', "%$q%")
                   ->orWhere('marca', 'like', "%$q%");
            });
        }
        if ($tipo) {
            $query->where('tipo', $tipo);
        }
        $items = $query->get();

        $pdf = Pdf::loadView('reportes.productos', [
            'items'   => $items,
            'q'       => $q,
            'tipo'    => $tipo,
            'total'   => $items->count(),
        ])->setPaper('letter', 'landscape');

        return $pdf->stream('productos_' . now()->format('Ymd_His') . '.pdf');
    }

    public function exportProductosExcel(Request $request)
    {
        $this->req($request, 'Ver Productos');
        $filters = $request->only(['q', 'tipo']);
        return Excel::download(new ProductosExport($filters), 'productos_' . now()->format('Ymd_His') . '.xlsx');
    }

    // ── Exportar Fabricantes ──────────────────────────────────────

    public function exportFabricantesPdf(Request $request)
    {
        $this->req($request, 'Ver Productos');
        $q     = $request->input('q', '');
        $query = Fabricante::orderBy('nombre');
        if ($q) {
            $query->where(function ($sq) use ($q) {
                $sq->where('nombre', 'like', "%$q%")
                   ->orWhere('pais',  'like', "%$q%");
            });
        }
        $items = $query->get();

        $pdf = Pdf::loadView('reportes.fabricantes', [
            'items' => $items,
            'q'     => $q,
            'total' => $items->count(),
        ])->setPaper('letter', 'portrait');

        return $pdf->stream('fabricantes_' . now()->format('Ymd_His') . '.pdf');
    }

    public function exportFabricantesExcel(Request $request)
    {
        $this->req($request, 'Ver Productos');
        $filters = $request->only(['q']);
        return Excel::download(new FabricantesExport($filters), 'fabricantes_' . now()->format('Ymd_His') . '.xlsx');
    }

    // ── Exportar Unidades ─────────────────────────────────────────

    public function exportUnidadesPdf(Request $request)
    {
        $this->req($request, 'Ver Productos');
        $q     = $request->input('q', '');
        $query = Unidad::orderBy('nombre');
        if ($q) {
            $query->where(function ($sq) use ($q) {
                $sq->where('nombre',       'like', "%$q%")
                   ->orWhere('abreviatura', 'like', "%$q%");
            });
        }
        $items = $query->get();

        $pdf = Pdf::loadView('reportes.unidades', [
            'items' => $items,
            'q'     => $q,
            'total' => $items->count(),
        ])->setPaper('letter', 'portrait');

        return $pdf->stream('unidades_' . now()->format('Ymd_His') . '.pdf');
    }

    public function exportUnidadesExcel(Request $request)
    {
        $this->req($request, 'Ver Productos');
        $filters = $request->only(['q']);
        return Excel::download(new UnidadesExport($filters), 'unidades_' . now()->format('Ymd_His') . '.xlsx');
    }

    // ── Helper ────────────────────────────────────────────────────

    private function req(Request $request, string|array $permission): void
    {
        $user  = $request->user();
        $perms = is_array($permission) ? $permission : [$permission];
        foreach ($perms as $p) {
            if ($user->hasPermissionTo($p)) return;
        }
        abort(403, 'No tiene permiso para realizar esta acción');
    }
}
