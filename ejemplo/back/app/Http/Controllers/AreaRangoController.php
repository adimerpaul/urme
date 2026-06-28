<?php

namespace App\Http\Controllers;

use App\Models\AreaRango;
use Illuminate\Http\Request;

class AreaRangoController extends Controller
{
    /**
     * GET /api/area-rangos?area_id=1&search=hemoglobina
     */
    public function index(Request $request)
    {
        $query = AreaRango::orderBy('rango_nombre');

        if ($request->filled('area_id')) {
            $query->where('area_id', $request->area_id);
        }

        if ($search = $request->get('search')) {
            $search = trim($search);
            $query->where(function ($q) use ($search) {
                $q->where('rango_nombre', 'like', "%{$search}%")
                    ->orWhere('unidad', 'like', "%{$search}%")
                    ->orWhere('interpretacion', 'like', "%{$search}%");
            });
        }

        return $query->get();
    }

    /**
     * GET /api/area-rangos/{id}
     */
    public function show($id)
    {
        return AreaRango::findOrFail($id);
    }

    /**
     * POST /api/area-rangos
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'area_id'              => 'required|exists:areas,id',
            'analito'              => 'nullable|string|max:255',
            'metodo'               => 'nullable|string|max:50',
            'resultado'            => 'nullable|string|max:100',
            'rango_nombre'         => 'nullable|string|max:255',
            'rango_descripcion'    => 'nullable|string|max:150',
            'rango_minimo'         => 'nullable|numeric',
            'rango_maximo'         => 'nullable|numeric',
            'rango_2_descripcion'  => 'nullable|string|max:150',
            'rango_2_minimo'       => 'nullable|numeric',
            'rango_2_maximo'       => 'nullable|numeric',
            'rango_3_descripcion'  => 'nullable|string|max:150',
            'rango_3_minimo'       => 'nullable|numeric',
            'rango_3_maximo'       => 'nullable|numeric',
            'rango_4_descripcion'  => 'nullable|string|max:150',
            'rango_4_minimo'       => 'nullable|numeric',
            'rango_4_maximo'       => 'nullable|numeric',
            'rango_5_descripcion'  => 'nullable|string|max:150',
            'rango_5_minimo'       => 'nullable|numeric',
            'rango_5_maximo'       => 'nullable|numeric',
            'unidad'               => 'nullable|string|max:255',
            'interpretacion'       => 'nullable|string',
            'muestra'              => 'nullable|string|max:100',
            'marca'                => 'nullable|string|max:100',
            'perfil'               => 'nullable|string|max:150',
        ]);

        // rango_nombre cae en analito si no se especifica
        $data['rango_nombre'] = $data['rango_nombre'] ?: ($data['analito'] ?? '');

        $rango = AreaRango::create($data);

        return response()->json($rango, 201);
    }

    /**
     * PUT /api/area-rangos/{id}
     */
    public function update(Request $request, $id)
    {
        $rango = AreaRango::findOrFail($id);

        $data = $request->validate([
            'area_id'              => 'required|exists:areas,id',
            'analito'              => 'nullable|string|max:255',
            'metodo'               => 'nullable|string|max:50',
            'resultado'            => 'nullable|string|max:100',
            'rango_nombre'         => 'nullable|string|max:255',
            'rango_descripcion'    => 'nullable|string|max:150',
            'rango_minimo'         => 'nullable|numeric',
            'rango_maximo'         => 'nullable|numeric',
            'rango_2_descripcion'  => 'nullable|string|max:150',
            'rango_2_minimo'       => 'nullable|numeric',
            'rango_2_maximo'       => 'nullable|numeric',
            'rango_3_descripcion'  => 'nullable|string|max:150',
            'rango_3_minimo'       => 'nullable|numeric',
            'rango_3_maximo'       => 'nullable|numeric',
            'rango_4_descripcion'  => 'nullable|string|max:150',
            'rango_4_minimo'       => 'nullable|numeric',
            'rango_4_maximo'       => 'nullable|numeric',
            'rango_5_descripcion'  => 'nullable|string|max:150',
            'rango_5_minimo'       => 'nullable|numeric',
            'rango_5_maximo'       => 'nullable|numeric',
            'unidad'               => 'nullable|string|max:255',
            'interpretacion'       => 'nullable|string',
            'muestra'              => 'nullable|string|max:100',
            'marca'                => 'nullable|string|max:100',
            'perfil'               => 'nullable|string|max:150',
        ]);

        $data['rango_nombre'] = $data['rango_nombre'] ?: ($data['analito'] ?? $rango->rango_nombre);

        $rango->update($data);

        return $rango;
    }

    /**
     * DELETE /api/area-rangos/{id}
     */
    public function destroy($id)
    {
        $rango = AreaRango::findOrFail($id);
        $rango->delete();

        return response()->json([
            'message' => 'Rango eliminado',
        ]);
    }
}
