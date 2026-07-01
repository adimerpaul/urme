<?php

namespace App\Http\Controllers;

use App\Models\Seguro;
use Illuminate\Http\Request;

class SeguroController extends Controller
{
    public function index(Request $request)
    {
        $this->req($request, 'Ver Seguros');

        $q       = $request->input('q', '');
        $perPage = $request->input('per_page');

        $query = Seguro::orderBy('nombre');
        if ($q) {
            $query->where(function ($sq) use ($q) {
                $sq->where('nombre', 'like', "%$q%")
                   ->orWhere('nit',   'like', "%$q%");
            });
        }

        if ($perPage) {
            return response()->json($query->paginate((int) $perPage));
        }
        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $this->req($request, 'Crear Seguros');
        $request->validate(['nombre' => 'required|string|max:255']);
        $seguro = Seguro::create([
            'nombre' => mb_strtoupper($request->nombre),
            'nit'    => $request->nit ? mb_strtoupper($request->nit) : null,
        ]);
        return response()->json($seguro, 201);
    }

    public function update(Request $request, $id)
    {
        $this->req($request, 'Editar Seguros');
        $request->validate(['nombre' => 'required|string|max:255']);
        $seguro = Seguro::findOrFail($id);
        $seguro->update([
            'nombre' => mb_strtoupper($request->nombre),
            'nit'    => $request->nit ? mb_strtoupper($request->nit) : null,
        ]);
        return response()->json($seguro);
    }

    public function destroy(Request $request, $id)
    {
        $this->req($request, 'Eliminar Seguros');
        Seguro::findOrFail($id)->delete();
        return response()->json(['message' => 'Seguro eliminado']);
    }

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
