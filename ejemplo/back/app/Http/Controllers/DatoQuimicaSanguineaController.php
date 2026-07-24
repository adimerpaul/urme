<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\DatoQuimicaSanguinea;
use Illuminate\Http\Request;

class DatoQuimicaSanguineaController extends Controller
{
    public function index()
    {
        $datos = DatoQuimicaSanguinea::with('prestaciones:id,nombre')
            ->orderBy('orden')
            ->get();

        $areaQuimica = Area::where('title', 'QUÍMICA SANGUÍNEA Y SEROLOGÍA')
            ->orWhere('title', 'Química Sanguínea y Serología')
            ->first();

        $servicios = [];
        if ($areaQuimica) {
            $servicios = \App\Models\Servicio::where('area_id', $areaQuimica->id)
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
        $dato = DatoQuimicaSanguinea::findOrFail($id);

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
