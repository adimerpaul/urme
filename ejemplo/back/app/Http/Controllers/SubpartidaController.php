<?php

namespace App\Http\Controllers;

use App\Models\Subpartida;
use Illuminate\Http\Request;

class SubpartidaController extends Controller
{
    public function index(Request $request)
    {
        $query = Subpartida::with('partida.grupo')->orderBy('num');

        if ($request->filled('partida_id')) {
            $query->where('partida_id', $request->partida_id);
        }

        return $query->get();
    }

    public function show($id)
    {
        return Subpartida::with('partida.grupo')->findOrFail($id);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'partida_id' => 'required|exists:partidas,id',
            'num' => 'required|integer|min:0',
            'codigo' => 'required|string|max:20|unique:subpartidas,codigo',
            'nombre' => 'required|string|max:255',
        ]);

        return response()->json(Subpartida::create($data)->load('partida.grupo'), 201);
    }

    public function update(Request $request, $id)
    {
        $subpartida = Subpartida::findOrFail($id);

        $data = $request->validate([
            'partida_id' => 'sometimes|required|exists:partidas,id',
            'num' => 'sometimes|required|integer|min:0',
            'codigo' => 'sometimes|required|string|max:20|unique:subpartidas,codigo,' . $subpartida->id,
            'nombre' => 'sometimes|required|string|max:255',
        ]);

        $subpartida->update($data);

        return response()->json($subpartida->load('partida.grupo'));
    }

    public function destroy($id)
    {
        $subpartida = Subpartida::findOrFail($id);
        $subpartida->delete();

        return response()->json(['message' => 'Subpartida eliminada correctamente']);
    }
}
