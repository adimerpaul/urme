<?php

namespace App\Http\Controllers;

use App\Models\Unidad;
use Illuminate\Http\Request;

class UnidadController extends Controller
{
    public function index()
    {
        return Unidad::orderBy('nombre')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255|unique:unidades,nombre',
        ]);

        $data['nombre'] = trim($data['nombre']);

        return response()->json(Unidad::create($data), 201);
    }

    public function update(Request $request, $id)
    {
        $unidad = Unidad::findOrFail($id);

        $data = $request->validate([
            'nombre' => "required|string|max:255|unique:unidades,nombre,{$id}",
        ]);

        $data['nombre'] = trim($data['nombre']);
        $unidad->update($data);

        return response()->json($unidad);
    }

    public function destroy($id)
    {
        $unidad = Unidad::findOrFail($id);
        $unidad->delete();
        return response()->json(['message' => 'Unidad eliminada']);
    }
}
