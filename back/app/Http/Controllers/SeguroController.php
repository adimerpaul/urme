<?php

namespace App\Http\Controllers;

use App\Models\Seguro;
use Illuminate\Http\Request;

class SeguroController extends Controller
{
    public function index(Request $request)
    {
        $this->req($request, ['Ver Seguros', 'Ver Ventas', 'Crear Ventas', 'Ver Pacientes', 'Crear Pacientes', 'Editar Pacientes']);

        $q = $request->input('q', '');
        $perPage = $request->input('per_page');

        $query = Seguro::orderBy('nombre');
        if ($q) {
            $query->where(function ($sq) use ($q) {
                $sq->where('nombre', 'like', "%$q%")
                    ->orWhere('nit', 'like', "%$q%");
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
            'nit' => $request->nit ? mb_strtoupper($request->nit) : null,
        ]);

        return response()->json($seguro, 201);
    }

    public function detalle(Request $request, $id)
    {
        $this->req($request, 'Ver Seguros');

        $seguro = Seguro::with([
            'pacientes:id,seguro_id,nombre_completo,ci,telefono',
            'internaciones' => fn ($query) => $query
                ->with(['paciente:id,nombre_completo,ci', 'items:id,internacion_id,nombre,cantidad,precio,total'])
                ->orderByDesc('fecha_ingreso')
                ->orderByDesc('id'),
        ])->findOrFail($id);

        $total = $seguro->internaciones->sum(fn ($internacion) => $internacion->items->sum('total'));

        return response()->json([
            'seguro' => $seguro->only(['id', 'nombre', 'nit']),
            'pacientes' => $seguro->pacientes,
            'internaciones' => $seguro->internaciones,
            'resumen' => [
                'cantidad_pacientes' => $seguro->pacientes->count(),
                'pacientes_internados' => $seguro->internaciones->pluck('paciente_id')->unique()->count(),
                'cantidad_internaciones' => $seguro->internaciones->count(),
                'total' => round((float) $total, 2),
            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $this->req($request, 'Editar Seguros');
        $request->validate(['nombre' => 'required|string|max:255']);
        $seguro = Seguro::findOrFail($id);
        $seguro->update([
            'nombre' => mb_strtoupper($request->nombre),
            'nit' => $request->nit ? mb_strtoupper($request->nit) : null,
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
        $user = $request->user();
        $perms = is_array($permission) ? $permission : [$permission];
        foreach ($perms as $p) {
            if ($user->hasPermissionTo($p)) {
                return;
            }
        }
        abort(403, 'No tiene permiso para realizar esta acción');
    }
}
