<?php

namespace App\Http\Controllers;

use App\Models\Partida;
use Illuminate\Http\Request;

class PartidaController extends Controller
{
    public function index(Request $request)
    {
        $query = Partida::with('grupo')->orderBy('num');

        if ($request->filled('grupo_id')) {
            $query->where('grupo_id', $request->grupo_id);
        }

        if ($request->boolean('with_subpartidas')) {
            $query->with('subpartidas');
        }

        return $query->get();
    }

    public function show($id)
    {
        return Partida::with(['grupo', 'subpartidas'])->findOrFail($id);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'grupo_id' => 'required|exists:grupos,id',
            'num' => 'required|integer|min:0',
            'codigo' => 'required|string|max:20|unique:partidas,codigo',
            'nombre' => 'required|string|max:255',
        ]);

        return response()->json(Partida::create($data)->load('grupo'), 201);
    }

    public function update(Request $request, $id)
    {
        $partida = Partida::findOrFail($id);

        $data = $request->validate([
            'grupo_id' => 'sometimes|required|exists:grupos,id',
            'num' => 'sometimes|required|integer|min:0',
            'codigo' => 'sometimes|required|string|max:20|unique:partidas,codigo,' . $partida->id,
            'nombre' => 'sometimes|required|string|max:255',
        ]);

        $partida->update($data);

        return response()->json($partida->load('grupo'));
    }

    public function destroy($id)
    {
        $partida = Partida::findOrFail($id);
        $partida->delete();

        return response()->json(['message' => 'Partida eliminada correctamente']);
    }
}
