<?php

namespace App\Http\Controllers;

use App\Models\Grupo;
use Illuminate\Http\Request;

class GrupoController extends Controller
{
    public function index(Request $request)
    {
        $query = Grupo::query()->orderBy('num');

        if ($request->boolean('with_partidas')) {
            $query->with('partidas.subpartidas');
        }

        return $query->get();
    }

    public function show($id)
    {
        return Grupo::with('partidas.subpartidas')->findOrFail($id);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'num' => 'required|integer|min:0',
            'codigo' => 'required|string|max:20|unique:grupos,codigo',
            'nombre' => 'required|string|max:255',
        ]);

        return response()->json(Grupo::create($data), 201);
    }

    public function update(Request $request, $id)
    {
        $grupo = Grupo::findOrFail($id);

        $data = $request->validate([
            'num' => 'sometimes|required|integer|min:0',
            'codigo' => 'sometimes|required|string|max:20|unique:grupos,codigo,' . $grupo->id,
            'nombre' => 'sometimes|required|string|max:255',
        ]);

        $grupo->update($data);

        return response()->json($grupo);
    }

    public function destroy($id)
    {
        $grupo = Grupo::findOrFail($id);
        $grupo->delete();

        return response()->json(['message' => 'Grupo eliminado correctamente']);
    }
}
