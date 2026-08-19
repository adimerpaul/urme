<?php

namespace App\Http\Controllers;

use App\Models\Paciente;
use Illuminate\Http\Request;

class PacienteController extends Controller
{
    public function index(Request $request)
    {
        $this->req($request, ['Ver Pacientes', 'Ver Ventas', 'Crear Ventas']);

        $q = $request->input('q', '');
        $tipoPaciente = $request->input('tipo_paciente', '');
        $estadoInternacion = $request->input('estado_internacion', '');
        $altaDesde = $request->input('alta_desde', '');
        $altaHasta = $request->input('alta_hasta', '');
        $perPage = (int) $request->input('per_page', 10);

        $query = Paciente::with(['latestInternacion', 'seguro'])->orderBy('nombre_completo');

        if ($q) {
            $query->where(function ($sq) use ($q) {
                $sq->where('nombre_completo', 'like', "%$q%")
                    ->orWhere('ci', 'like', "%$q%");
            });
        }

        if ($tipoPaciente) {
            $query->whereHas('latestInternacion', fn ($sq) => $sq->where('tipo_paciente', $tipoPaciente));
        }

        if ($estadoInternacion === 'NO_INTERNADO') {
            $query->doesntHave('internaciones');
        } elseif ($estadoInternacion === 'INTERNADO') {
            $query->whereHas('latestInternacion', fn ($sq) => $sq->whereNull('fecha_alta'));
        } elseif ($estadoInternacion === 'ALTA') {
            $query->whereHas('latestInternacion', fn ($sq) => $sq->whereNotNull('fecha_alta'));
        }

        if ($altaDesde) {
            $query->whereHas('latestInternacion', fn ($sq) => $sq->whereDate('fecha_alta', '>=', $altaDesde));
        }
        if ($altaHasta) {
            $query->whereHas('latestInternacion', fn ($sq) => $sq->whereDate('fecha_alta', '<=', $altaHasta));
        }

        return response()->json($query->paginate($perPage));
    }

    /**
     * Internaciones del paciente, la más reciente primero.
     * Accesible también desde la venta (para ver si tiene una internación sin alta).
     */
    public function internaciones(Request $request, $id)
    {
        $this->req($request, ['Ver Internaciones', 'Ver Ventas', 'Crear Ventas']);

        $paciente = Paciente::findOrFail($id);

        $query = $paciente->internaciones()->with('seguro:id,nombre')->orderByDesc('fecha_ingreso')->orderByDesc('id');

        if ($request->boolean('abiertas')) {
            $query->whereNull('fecha_alta');
        }

        return response()->json($query->get());
    }

    public function show(Request $request, $id)
    {
        $this->req($request, 'Ver Pacientes');
        $paciente = Paciente::with(['seguro', 'internaciones.seguro:id,nombre', 'internaciones.items.producto:id,nombre', 'internaciones.items.user:id,name'])->findOrFail($id);

        return response()->json($paciente);
    }

    public function store(Request $request)
    {
        $this->req($request, ['Crear Pacientes', 'Crear Ventas', 'Crear Solicitudes Laboratorio']);
        $request->validate([
            'nombre_completo' => 'required|string|max:255',
            'sexo' => 'nullable|in:M,F',
            'seguro_id' => 'nullable|exists:seguros,id',
        ]);
        $paciente = Paciente::create($request->only(['seguro_id', 'nombre_completo', 'sexo', 'fecha_nacimiento', 'ci', 'estado', 'direccion', 'telefono']));

        return response()->json($paciente, 201);
    }

    public function update(Request $request, $id)
    {
        $this->req($request, 'Editar Pacientes');
        $request->validate([
            'nombre_completo' => 'required|string|max:255',
            'sexo' => 'nullable|in:M,F',
            'seguro_id' => 'nullable|exists:seguros,id',
        ]);
        $paciente = Paciente::findOrFail($id);
        $paciente->update($request->only(['seguro_id', 'nombre_completo', 'sexo', 'fecha_nacimiento', 'ci', 'estado', 'direccion', 'telefono']));

        return response()->json($paciente);
    }

    public function destroy(Request $request, $id)
    {
        $this->req($request, 'Eliminar Pacientes');
        Paciente::findOrFail($id)->delete();

        return response()->json(['message' => 'Paciente eliminado']);
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
