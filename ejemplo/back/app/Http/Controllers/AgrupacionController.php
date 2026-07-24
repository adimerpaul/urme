<?php

namespace App\Http\Controllers;

use App\Models\Agrupacion;
use App\Models\Servicio;
use Illuminate\Http\Request;

class AgrupacionController extends Controller
{
    public function index()
    {
        return response()->json([
            'agrupaciones' => Agrupacion::with('prestaciones:id,nombre,codigo')
                ->orderBy('orden')
                ->get(),
            'servicios' => Servicio::orderBy('nombre')
                ->get(['id', 'nombre', 'codigo', 'estado']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'activo' => 'sometimes|boolean',
            'servicio_ids' => 'sometimes|array',
            'servicio_ids.*' => 'integer|exists:servicios,id',
        ]);

        $agrupacion = Agrupacion::create([
            'nombre' => $data['nombre'],
            'activo' => $data['activo'] ?? true,
            'orden' => ((int) Agrupacion::max('orden')) + 10,
        ]);

        $agrupacion->prestaciones()->sync($data['servicio_ids'] ?? []);

        return response()->json($agrupacion->load('prestaciones:id,nombre,codigo'), 201);
    }

    public function update(Request $request, $id)
    {
        $agrupacion = Agrupacion::findOrFail($id);

        $data = $request->validate([
            'nombre' => 'sometimes|string|max:255',
            'activo' => 'sometimes|boolean',
            'servicio_ids' => 'sometimes|array',
            'servicio_ids.*' => 'integer|exists:servicios,id',
        ]);

        $agrupacion->fill(collect($data)->only(['nombre', 'activo'])->all());
        $agrupacion->save();

        if (array_key_exists('servicio_ids', $data)) {
            $agrupacion->prestaciones()->sync($data['servicio_ids']);
        }

        return response()->json($agrupacion->load('prestaciones:id,nombre,codigo'));
    }

    public function destroy($id)
    {
        $agrupacion = Agrupacion::findOrFail($id);
        $agrupacion->prestaciones()->detach();
        $agrupacion->delete();

        return response()->json(['message' => 'Agrupación eliminada']);
    }
}
