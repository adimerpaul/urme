<?php

namespace App\Http\Controllers;

use App\Models\Diagnostico;
use Illuminate\Http\Request;

class DiagnosticoController extends Controller
{
    public function index(Request $request)
    {
        $query = Diagnostico::orderBy('especialidad')->orderBy('cie10');

        if ($request->filled('search')) {
            $s = $request->get('search');
            $query->where(function ($q) use ($s) {
                $q->where('cie10',         'like', "%{$s}%")
                  ->orWhere('especialidad', 'like', "%{$s}%")
                  ->orWhere('servicio',     'like', "%{$s}%");
            });
        }

        // Con per_page → paginado (panel admin); sin él → todos (select del formulario)
        if ($request->filled('per_page')) {
            return response()->json($query->paginate((int) $request->get('per_page', 20)));
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'cie10'        => 'required|string|max:255',
            'especialidad' => 'required|string|max:255',
            'servicio'     => 'required|string|max:255',
        ]);

        return response()->json(Diagnostico::create($data), 201);
    }

    public function update(Request $request, $id)
    {
        $diagnostico = Diagnostico::findOrFail($id);

        $data = $request->validate([
            'cie10'        => 'required|string|max:255',
            'especialidad' => 'required|string|max:255',
            'servicio'     => 'required|string|max:255',
        ]);

        $diagnostico->update($data);
        return response()->json($diagnostico);
    }

    public function destroy($id)
    {
        Diagnostico::findOrFail($id)->delete();
        return response()->json(['message' => 'Diagnóstico eliminado']);
    }
}
