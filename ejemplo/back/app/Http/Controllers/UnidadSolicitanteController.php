<?php

namespace App\Http\Controllers;

use App\Models\UnidadSolicitante;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UnidadSolicitanteController extends Controller
{
    public function index(Request $request)
    {
        $query = UnidadSolicitante::query();

        if ($request->filled('q')) {
            $q = trim((string) $request->q);
            $query->where(function ($sub) use ($q) {
                $sub->where('nombre', 'like', "%{$q}%")
                    ->orWhere('abreviatura', 'like', "%{$q}%");
            });
        }

        return $query->orderBy('nombre')->get();
    }

    public function show($id)
    {
        return UnidadSolicitante::findOrFail($id);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'      => ['required', 'string', 'max:255', 'unique:unidad_solicitantes,nombre'],
            'abreviatura' => ['required', 'string', 'max:20',  'unique:unidad_solicitantes,abreviatura'],
        ]);

        $data['nombre']      = trim($data['nombre']);
        $data['abreviatura'] = strtoupper(trim($data['abreviatura']));

        return response()->json(UnidadSolicitante::create($data), 201);
    }

    public function update(Request $request, $id)
    {
        $unidadSolicitante = UnidadSolicitante::findOrFail($id);

        $data = $request->validate([
            'nombre' => [
                'required', 'string', 'max:255',
                Rule::unique('unidad_solicitantes', 'nombre')->ignore($unidadSolicitante->id),
            ],
            'abreviatura' => [
                'required', 'string', 'max:20',
                Rule::unique('unidad_solicitantes', 'abreviatura')->ignore($unidadSolicitante->id),
            ],
        ]);

        $data['nombre']      = trim($data['nombre']);
        $data['abreviatura'] = strtoupper(trim($data['abreviatura']));

        $unidadSolicitante->update($data);

        // Actualizar el snapshot de sala en solicitudes con la abreviatura
        $unidadSolicitante->solicitudes()->update(['sala' => $unidadSolicitante->abreviatura]);

        return response()->json($unidadSolicitante);
    }

    public function destroy($id)
    {
        $unidadSolicitante = UnidadSolicitante::findOrFail($id);
        $unidadSolicitante->solicitudes()->update([
            'unidad_solicitante_id' => null,
        ]);

        $unidadSolicitante->delete();

        return response()->json(['message' => 'Unidad solicitante eliminada correctamente']);
    }
}
