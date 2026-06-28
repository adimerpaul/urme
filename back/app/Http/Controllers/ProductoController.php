<?php

namespace App\Http\Controllers;

use App\Models\Fabricante;
use App\Models\Producto;
use App\Models\Unidad;
use Illuminate\Http\Request;

class ProductoController extends Controller
{
    // ── Catálogos (usados en selects del frontend) ────────────

    public function fabricantes(Request $request)
    {
        $this->req($request, 'Ver Productos');
        return response()->json(Fabricante::orderBy('nombre')->get());
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
        return response()->json(Unidad::orderBy('nombre')->get());
    }

    public function storeUnidad(Request $request)
    {
        $this->req($request, 'Crear Productos');
        $request->validate(['nombre' => 'required|string|max:255']);
        $u = Unidad::create([
            'nombre'       => mb_strtoupper($request->nombre),
            'abreviatura'  => $request->abreviatura ? mb_strtoupper($request->abreviatura) : null,
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

    // ── Productos ─────────────────────────────────────────────

    public function index(Request $request)
    {
        $this->req($request, 'Ver Productos');

        $q = $request->input('q', '');
        $tipo = $request->input('tipo', '');
        $perPage = (int) $request->input('per_page', 25);

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
            'codigo'        => $request->codigo ? mb_strtoupper($request->codigo) : null,
            'nombre'        => mb_strtoupper($request->nombre),
            'descripcion'   => $request->descripcion ? mb_strtoupper($request->descripcion) : null,
            'marca'         => $request->marca ? mb_strtoupper($request->marca) : null,
            'fabricante_id' => $request->fabricante_id ?: null,
            'unidad_id'     => $request->unidad_id ?: null,
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
            'codigo'        => $request->codigo ? mb_strtoupper($request->codigo) : null,
            'nombre'        => mb_strtoupper($request->nombre),
            'descripcion'   => $request->descripcion ? mb_strtoupper($request->descripcion) : null,
            'marca'         => $request->marca ? mb_strtoupper($request->marca) : null,
            'fabricante_id' => $request->fabricante_id ?: null,
            'unidad_id'     => $request->unidad_id ?: null,
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

    // ── Helper ────────────────────────────────────────────────

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
