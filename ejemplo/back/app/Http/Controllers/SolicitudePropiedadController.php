<?php

namespace App\Http\Controllers;

use App\Models\SolicitudePropiedad;
use Illuminate\Http\Request;

class SolicitudePropiedadController extends Controller
{
    public function index(Request $request)
    {
        $query = SolicitudePropiedad::query();

        if ($request->filled('solicitude_id')) {
            $query->where('solicitude_id', $request->solicitude_id);
        }

        if ($request->filled('area_id')) {
            $query->where('area_id', $request->area_id);
        }

        return $query->orderBy('id')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'solicitude_id' => 'required|exists:solicitudes,id',
            'area_id'       => 'required|exists:areas,id',
            'campo'         => 'required|string|max:255',
            'valor'         => 'nullable|string',
        ]);

        $propiedad = SolicitudePropiedad::create($data);

        return response()->json($propiedad, 201);
    }

    public function show(SolicitudePropiedad $solicitudePropiedad)
    {
        return response()->json($solicitudePropiedad);
    }

    public function update(Request $request, SolicitudePropiedad $solicitudePropiedad)
    {
        $data = $request->validate([
            'campo' => 'sometimes|required|string|max:255',
            'valor' => 'nullable|string',
        ]);

        $solicitudePropiedad->update($data);

        return response()->json($solicitudePropiedad);
    }

    public function destroy(SolicitudePropiedad $solicitudePropiedad)
    {
        $solicitudePropiedad->delete();

        return response()->json(['message' => 'Propiedad eliminada correctamente']);
    }
}
