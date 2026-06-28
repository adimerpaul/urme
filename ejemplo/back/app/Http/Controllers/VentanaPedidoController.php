<?php

namespace App\Http\Controllers;

use App\Models\VentanaPedido;
use Illuminate\Http\Request;

class VentanaPedidoController extends Controller
{
    public function index()
    {
        return VentanaPedido::with('user:id,name')
            ->orderBy('fecha_inicio', 'desc')
            ->get();
    }

    public function vigente()
    {
        return response()->json(VentanaPedido::vigente());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin'    => 'required|date|after_or_equal:fecha_inicio',
            'descripcion'  => 'nullable|string|max:255',
            'activo'       => 'boolean',
        ]);

        $ventana = VentanaPedido::create([
            ...$data,
            'user_id' => $request->user()->id,
            'activo'  => $data['activo'] ?? true,
        ]);

        return response()->json($ventana->load('user:id,name'), 201);
    }

    public function update(Request $request, $id)
    {
        $ventana = VentanaPedido::findOrFail($id);

        $data = $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin'    => 'required|date|after_or_equal:fecha_inicio',
            'descripcion'  => 'nullable|string|max:255',
            'activo'       => 'boolean',
        ]);

        $ventana->update($data);

        return response()->json($ventana->load('user:id,name'));
    }

    public function destroy($id)
    {
        VentanaPedido::findOrFail($id)->delete();
        return response()->json(['message' => 'Ventana eliminada']);
    }
}
