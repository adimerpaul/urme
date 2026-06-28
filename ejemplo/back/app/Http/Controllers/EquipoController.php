<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use Illuminate\Http\Request;

class EquipoController extends Controller
{
    public function index(Request $request)
    {
        $query = Equipo::query()->whereNull('deleted_at');

        if ($request->filled('estado')) {
            $query->where('estado', $request->get('estado'));
        }
        if ($request->filled('servicio_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('servicio_id', $request->get('servicio_id'))
                  ->orWhereNull('servicio_id');
            });
        }
        if ($request->filled('q')) {
            $query->where('nombre', 'like', '%' . $request->get('q') . '%');
        }

        return response()->json(
            $query->orderBy('nombre')->get()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'      => 'required|string|max:120',
            'estado'      => 'required|in:ACTIVO,INACTIVO',
            'servicio_id' => 'nullable|integer|exists:servicios,id',
        ]);

        $equipo = Equipo::create($data);

        return response()->json($equipo, 201);
    }

    public function show(string $id)
    {
        return response()->json(Equipo::findOrFail($id));
    }

    public function update(Request $request, string $id)
    {
        $equipo = Equipo::findOrFail($id);

        $data = $request->validate([
            'nombre'      => 'required|string|max:120',
            'estado'      => 'required|in:ACTIVO,INACTIVO',
            'servicio_id' => 'nullable|integer|exists:servicios,id',
        ]);

        $equipo->update($data);

        return response()->json($equipo->fresh());
    }

    public function destroy(string $id)
    {
        Equipo::findOrFail($id)->delete();

        return response()->json(['message' => 'Equipo eliminado']);
    }
}
