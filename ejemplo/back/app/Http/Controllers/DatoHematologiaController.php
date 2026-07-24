<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\DatoHematologia;
use Illuminate\Http\Request;

class DatoHematologiaController extends Controller
{
    public function index()
    {
        $datos = DatoHematologia::with('prestaciones:id,nombre')
            ->orderBy('orden')
            ->get();

        $areaHemato = Area::where('title', 'HEMATOLOGÍA')
            ->orWhere('title', 'Hematología')
            ->first();

        $servicios = [];
        if ($areaHemato) {
            $servicios = \App\Models\Servicio::where('area_id', $areaHemato->id)
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'estado']);
        }

        return response()->json([
            'datos' => $datos,
            'servicios' => $servicios,
        ]);
    }

    public function update(Request $request, $id)
    {
        $dato = DatoHematologia::findOrFail($id);

        $data = $request->validate([
            'nombre' => 'sometimes|string|max:255',
            'activo' => 'sometimes|boolean',
            'servicio_ids' => 'sometimes|array',
            'servicio_ids.*' => 'integer|exists:servicios,id',
        ]);

        $dato->fill(collect($data)->only(['nombre', 'activo'])->all());
        $dato->save();

        if (array_key_exists('servicio_ids', $data)) {
            $dato->prestaciones()->sync($data['servicio_ids']);
        }

        return response()->json($dato->load('prestaciones:id,nombre'));
    }
}
