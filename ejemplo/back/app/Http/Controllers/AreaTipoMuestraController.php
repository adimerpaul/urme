<?php

namespace App\Http\Controllers;

use App\Models\AreaTipoMuestra;
use Illuminate\Http\Request;

class AreaTipoMuestraController extends Controller
{
    /**
     * GET /api/area-tipo-muestras?area_id=1
     */
    public function index(Request $request)
    {
        $query = AreaTipoMuestra::with('area')->orderBy('id', 'asc');

        if ($request->filled('area_id')) {
            $query->where('area_id', $request->area_id);
        }

        return $query->get();
    }

    /**
     * GET /api/area-tipo-muestras/{id}
     */
    public function show($id)
    {
        return AreaTipoMuestra::with('area')->findOrFail($id);
    }

    /**
     * POST /api/area-tipo-muestras
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'area_id'      => 'required|exists:areas,id',
            'tipo_muestra' => 'required|string|max:255',
        ]);

        $muestra = AreaTipoMuestra::create($data);

        return response()->json($muestra, 201);
    }

    /**
     * PUT /api/area-tipo-muestras/{id}
     */
    public function update(Request $request, $id)
    {
        $muestra = AreaTipoMuestra::findOrFail($id);

        $data = $request->validate([
            'area_id'      => 'sometimes|required|exists:areas,id',
            'tipo_muestra' => 'sometimes|required|string|max:255',
        ]);

        $muestra->update($data);

        return response()->json($muestra);
    }

    /**
     * DELETE /api/area-tipo-muestras/{id}
     */
    public function destroy($id)
    {
        $muestra = AreaTipoMuestra::findOrFail($id);
        $muestra->delete();

        return response()->json([
            'message' => 'Tipo de muestra eliminada correctamente'
        ]);
    }
}
