<?php

namespace App\Http\Controllers;

use App\Models\Formularios;
use Illuminate\Http\Request;

class FormulariosController extends Controller
{
    /**
     * Listar formularios con búsqueda y paginación.
     * GET /api/formularios?search=x&per_page=10
     */
    public function index(Request $request)
    {
        $search  = $request->input('search');
        $perPage =  10000;

        $query = Formularios::with('area');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                    ->orWhereHas('area', function ($q2) use ($search) {
                        $q2->where('nombre', 'like', "%{$search}%");
                    });
            });
        }

        $formularios = $query->orderBy('id', 'desc')->paginate($perPage);

        return response()->json($formularios);
    }

    /**
     * Crear formulario
     * POST /api/formularios
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'  => 'required|string|max:255',
            'area_id' => 'required|exists:areas,id',
            'html'    => 'nullable|string',
        ]);

        $formulario = Formularios::create($data);

        return response()->json($formulario->load('area'), 201);
    }

    /**
     * Ver formulario
     * GET /api/formularios/{id}
     */
    public function show($id)
    {
        $formulario = Formularios::with('area')->findOrFail($id);
        return response()->json($formulario);
    }

    /**
     * Actualizar formulario
     * PUT /api/formularios/{id}
     */
    public function update(Request $request, $id)
    {
        $formulario = Formularios::findOrFail($id);

        $data = $request->validate([
            'nombre'  => 'required|string|max:255',
            'area_id' => 'required|exists:areas,id',
            'html'    => 'nullable|string',
        ]);

        $formulario->update($data);

        return response()->json($formulario->load('area'));
    }

    /**
     * Eliminar (soft delete)
     * DELETE /api/formularios/{id}
     */
    public function destroy($id)
    {
        $formulario = Formularios::findOrFail($id);
        $formulario->delete();

        return response()->json(['message' => 'Formulario eliminado correctamente']);
    }
}
